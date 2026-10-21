<?php
namespace App\Models\Evolution;

use App\Converter\Evolution\PrimitiveToEvolutionConverter;
use App\Entity\Evolution\Evolution;
use App\Tenancy\TenantContext;
use CodeIgniter\Database\BaseConnection;
use Config\Database;

final class EvolutionModel
{
    private BaseConnection $db;
    private PrimitiveToEvolutionConverter $converter;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->converter = new PrimitiveToEvolutionConverter();
    }

    public function insert(Evolution $evolution): Evolution
    {
        $query = "INSERT INTO evolutions
                    (tenant_id, patient_id, medical_id, appointment_id, diagnosis, observations, treatment, indications, corrects_id)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $this->db->query($query, [
            TenantContext::id(),
            $evolution->getPatientId(),
            $evolution->getMedicalId(),
            $evolution->getAppointmentId(),
            $evolution->getDiagnosis(),
            $evolution->getObservations(),
            $evolution->getTreatment(),
            $evolution->getIndications(),
            $evolution->getCorrectsId(),
        ]);

        return $this->find($this->db->insertID());
    }

    /** Solo actualiza borradores: el `signed_at IS NULL` impide tocar una evolución ya firmada. */
    public function updateDraft(Evolution $evolution): Evolution
    {
        $query = "UPDATE evolutions
                  SET diagnosis = ?, observations = ?, treatment = ?, indications = ?
                  WHERE id = ? AND tenant_id = ? AND signed_at IS NULL";

        $this->db->query($query, [
            $evolution->getDiagnosis(),
            $evolution->getObservations(),
            $evolution->getTreatment(),
            $evolution->getIndications(),
            $evolution->getId(),
            TenantContext::id(),
        ]);

        return $this->find($evolution->getId());
    }

    /** Atómico: devuelve false si ya estaba firmada (dos firmas simultáneas no pisan `signed_at`). */
    public function signIfDraft(int $id): bool
    {
        $this->db->query(
            "UPDATE evolutions SET signed_at = NOW() WHERE id = ? AND tenant_id = ? AND signed_at IS NULL",
            [$id, TenantContext::id()]
        );

        return $this->db->affectedRows() > 0;
    }

    public function find(int $id): ?Evolution
    {
        $result = $this->db->query(
            "SELECT * FROM evolutions WHERE id = ? AND tenant_id = ?",
            [$id, TenantContext::id()]
        );
        $primitive = $result->getRow();

        return $primitive ? $this->converter->convert($primitive) : null;
    }

    /**
     * Evoluciones de un paciente, de la más reciente a la más antigua.
     * Con `$medicalId` solo las de ese médico (el médico ve únicamente las suyas); null = todas (admin).
     *
     * @return array{data: Evolution[], total: int}
     */
    public function findByPatient(int $patientId, ?int $medicalId, int $page, int $size): array
    {
        $where = "WHERE tenant_id = ? AND patient_id = ?";
        $params = [TenantContext::id(), $patientId];

        if ($medicalId !== null) {
            $where .= " AND medical_id = ?";
            $params[] = $medicalId;
        }

        $total = (int) $this->db->query("SELECT COUNT(*) AS total FROM evolutions $where", $params)->getRow()->total;

        $rows = $this->db->query(
            "SELECT * FROM evolutions $where ORDER BY created_at DESC, id DESC LIMIT ? OFFSET ?",
            [...$params, $size, ($page - 1) * $size]
        )->getResult();

        return [
            'data'  => array_map(fn (object $row) => $this->converter->convert($row), $rows),
            'total' => $total,
        ];
    }

    /**
     * Evolución vigente del turno: la que ninguna otra corrige. Una activa por turno;
     * una corrección reemplaza a la anterior sin duplicar.
     */
    public function findActiveByAppointment(int $appointmentId): ?Evolution
    {
        $result = $this->db->query(
            "SELECT e.* FROM evolutions e
             WHERE e.tenant_id = ? AND e.appointment_id = ?
               AND NOT EXISTS (
                   SELECT 1 FROM evolutions c
                   WHERE c.tenant_id = e.tenant_id AND c.corrects_id = e.id
               )
             ORDER BY e.id DESC LIMIT 1",
            [TenantContext::id(), $appointmentId]
        );
        $primitive = $result->getRow();

        return $primitive ? $this->converter->convert($primitive) : null;
    }
}
