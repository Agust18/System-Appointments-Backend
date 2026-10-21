<?php
namespace App\Models\Evolution;

use App\Converter\Evolution\PrimitiveToEvolutionConverter;
use App\Converter\Evolution\Projection\PrimitiveToEvolutionProjectionConverter;
use App\Entity\Evolution\Evolution;
use App\Entity\Evolution\Projection\EvolutionProjection;
use App\Tenancy\TenantContext;
use CodeIgniter\Database\BaseConnection;
use Config\Database;

final class EvolutionModel
{
    private BaseConnection $db;
    private PrimitiveToEvolutionConverter $converter;
    private PrimitiveToEvolutionProjectionConverter $projectionConverter;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->converter = new PrimitiveToEvolutionConverter();
        $this->projectionConverter = new PrimitiveToEvolutionProjectionConverter();
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
     * Evoluciones de un paciente, de la más reciente a la más antigua, con el nombre del médico que firma
     * y los datos del turno. Con `$medicalId` solo las de ese médico (el médico ve únicamente las suyas);
     * null = todas (admin).
     *
     * @return array{data: EvolutionProjection[], total: int}
     */
    public function findByPatient(int $patientId, ?int $medicalId, int $page, int $size): array
    {
        $where = "WHERE E.tenant_id = ? AND E.patient_id = ?";
        $params = [TenantContext::id(), $patientId];

        if ($medicalId !== null) {
            $where .= " AND E.medical_id = ?";
            $params[] = $medicalId;
        }

        $total = (int) $this->db->query("SELECT COUNT(*) AS total FROM evolutions E $where", $params)->getRow()->total;

        $rows = $this->db->query(
            "SELECT E.*,
                    TRIM(CONCAT(U.first_name, ' ', U.last_name)) AS medical_name,
                    A.fecha AS appointment_fecha,
                    A.hora  AS appointment_hora,
                    SRV.name AS service_name
             FROM evolutions E
             INNER JOIN medicals M   ON M.id = E.medical_id AND M.tenant_id = E.tenant_id
             INNER JOIN users U      ON U.id = M.user_id AND U.tenant_id = M.tenant_id
             INNER JOIN appointments A ON A.id = E.appointment_id AND A.tenant_id = E.tenant_id
             INNER JOIN services SRV ON SRV.id = A.service_id AND SRV.tenant_id = A.tenant_id
             $where
             ORDER BY E.created_at DESC, E.id DESC LIMIT ? OFFSET ?",
            [...$params, $size, ($page - 1) * $size]
        )->getResult();

        return [
            'data'  => array_map(fn (object $row) => $this->projectionConverter->convert($row), $rows),
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
