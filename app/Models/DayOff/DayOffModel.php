<?php
namespace App\Models\DayOff;

use App\Converter\DayOff\PrimitiveToDayOffConverter;
use App\Dto\Request\DayOff\DayOffFilterRequest;
use App\Entity\DayOff\DayOff;
use App\Tenancy\TenantContext;
use CodeIgniter\Database\BaseConnection;
use Config\Database;

final class DayOffModel
{
    private BaseConnection $db;
    private PrimitiveToDayOffConverter $converter;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->converter = new PrimitiveToDayOffConverter();
    }

    public function insert(DayOff $dayOff): DayOff
    {
        $query = "INSERT INTO medical_days_off (tenant_id, medical_id, date_from, date_to, reason, created_by, created_at)
                  VALUES (?, ?, ?, ?, ?, ?, NOW())";

        $this->db->query($query, [
            TenantContext::id(),
            $dayOff->getMedicalId(),
            $dayOff->getDateFrom(),
            $dayOff->getDateTo(),
            $dayOff->getReason(),
            $dayOff->getCreatedBy(),
        ]);

        return $this->find($this->db->insertID());
    }

    public function update(DayOff $dayOff): DayOff
    {
        $query = "UPDATE medical_days_off
                  SET medical_id = ?, date_from = ?, date_to = ?, reason = ?
                  WHERE id = ? AND tenant_id = ?";

        $this->db->query($query, [
            $dayOff->getMedicalId(),
            $dayOff->getDateFrom(),
            $dayOff->getDateTo(),
            $dayOff->getReason(),
            $dayOff->getId(),
            TenantContext::id(),
        ]);

        return $this->find($dayOff->getId());
    }

    public function find(int $id): ?DayOff
    {
        $result = $this->db->query(
            "SELECT * FROM medical_days_off WHERE id = ? AND tenant_id = ?",
            [$id, TenantContext::id()]
        );
        $primitive = $result->getRow();

        if (!$primitive) {
            return null;
        }

        return $this->converter->convert($primitive);
    }

    public function delete(int $id): void
    {
        $this->db->query("DELETE FROM medical_days_off WHERE id = ? AND tenant_id = ?", [$id, TenantContext::id()]);
    }

    /**
     * Bloqueo del mismo alcance (misma clínica entera, o el mismo médico) cuyas fechas se pisan
     * con [$dateFrom, $dateTo]. `<=>` compara también NULL con NULL (alcance "toda la clínica").
     */
    public function findOverlapping(?int $medicalId, string $dateFrom, string $dateTo, ?int $excludeId = null): ?DayOff
    {
        $query = "SELECT * FROM medical_days_off
                  WHERE tenant_id = ?
                    AND medical_id <=> ?
                    AND date_from <= ?
                    AND date_to >= ?";
        $params = [TenantContext::id(), $medicalId, $dateTo, $dateFrom];

        if ($excludeId !== null) {
            $query .= " AND id != ?";
            $params[] = $excludeId;
        }

        $primitive = $this->db->query($query . " LIMIT 1", $params)->getRow();

        if (!$primitive) {
            return null;
        }

        return $this->converter->convert($primitive);
    }

    /**
     * ¿Hay un bloqueo (de la clínica o del médico) que cubra la fecha?
     */
    public function existsForMedicalOnDate(int $medicalId, string $fecha): bool
    {
        $query = "SELECT 1 FROM medical_days_off
                  WHERE tenant_id = ?
                    AND (medical_id IS NULL OR medical_id = ?)
                    AND date_from <= ?
                    AND date_to >= ?
                  LIMIT 1";

        return $this->db->query($query, [TenantContext::id(), $medicalId, $fecha, $fecha])->getRow() !== null;
    }

    /**
     * Bloqueos (de la clínica o del médico) que se solapan con el rango, en una sola query
     * (usado por el endpoint batch de disponibilidad).
     *
     * @return DayOff[]
     */
    public function findByMedicalAndRange(int $medicalId, string $from, string $to): array
    {
        $query = "SELECT * FROM medical_days_off
                  WHERE tenant_id = ?
                    AND (medical_id IS NULL OR medical_id = ?)
                    AND date_from <= ?
                    AND date_to >= ?
                  ORDER BY date_from ASC";

        $result = $this->db->query($query, [TenantContext::id(), $medicalId, $to, $from]);

        return array_map(fn (object $primitive): DayOff => $this->converter->convert($primitive), $result->getResult());
    }

    /**
     * @return DayOff[]
     */
    public function search(DayOffFilterRequest $request): array
    {
        [$where, $parameters] = $this->buildWhereClause($request);

        $query = "SELECT * FROM medical_days_off WHERE {$where} ORDER BY date_from ASC, id ASC LIMIT ?, ?";
        $parameters[] = $request->getPagination()->getOffset();
        $parameters[] = $request->getPagination()->getLimit();

        $result = $this->db->query($query, $parameters);

        return array_map(fn (object $primitive): DayOff => $this->converter->convert($primitive), $result->getResult());
    }

    public function count(DayOffFilterRequest $request): int
    {
        [$where, $parameters] = $this->buildWhereClause($request);

        $row = $this->db->query("SELECT COUNT(*) AS total FROM medical_days_off WHERE {$where}", $parameters)->getRow();

        return (int) $row->total;
    }

    /**
     * @return array{0: string, 1: array<int, mixed>}
     */
    private function buildWhereClause(DayOffFilterRequest $request): array
    {
        $where = "tenant_id = ?";
        $parameters = [TenantContext::id()];

        if ($request->getMedicalId() !== null) {
            $where .= " AND (medical_id IS NULL OR medical_id = ?)";
            $parameters[] = $request->getMedicalId();
        }

        // Solapa con [from, to]: empieza antes de que termine el rango y termina después de que empiece.
        if ($request->getFrom() !== null) {
            $where .= " AND date_to >= ?";
            $parameters[] = $request->getFrom();
        }

        if ($request->getTo() !== null) {
            $where .= " AND date_from <= ?";
            $parameters[] = $request->getTo();
        }

        return [$where, $parameters];
    }
}
