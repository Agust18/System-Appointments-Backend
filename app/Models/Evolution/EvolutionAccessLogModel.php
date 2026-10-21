<?php
namespace App\Models\Evolution;

use App\Converter\Evolution\Projection\PrimitiveToEvolutionAccessLogProjectionConverter;
use App\Dto\Request\Evolution\EvolutionAccessLogFilterRequest;
use App\Entity\Evolution\Projection\EvolutionAccessLogProjection;
use App\Tenancy\TenantContext;
use CodeIgniter\Database\BaseConnection;
use Config\Database;

final class EvolutionAccessLogModel
{
    private BaseConnection $db;
    private PrimitiveToEvolutionAccessLogProjectionConverter $converter;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->converter = new PrimitiveToEvolutionAccessLogProjectionConverter();
    }

    public function insert(int $userId, int $patientId, ?int $evolutionId, string $action): void
    {
        $this->db->query(
            "INSERT INTO evolution_access_log (tenant_id, user_id, patient_id, evolution_id, action)
             VALUES (?, ?, ?, ?, ?)",
            [TenantContext::id(), $userId, $patientId, $evolutionId, $action]
        );
    }

    /**
     * Accesos del Tenant, del más reciente al más antiguo, con los nombres de quien accedió y del paciente.
     *
     * @return array{data: EvolutionAccessLogProjection[], total: int}
     */
    public function search(EvolutionAccessLogFilterRequest $request): array
    {
        $from = "FROM evolution_access_log L
                 INNER JOIN users U ON U.id = L.user_id AND U.tenant_id = L.tenant_id
                 INNER JOIN patients P ON P.id = L.patient_id AND P.tenant_id = L.tenant_id";
        $where = "WHERE L.tenant_id = ?";
        $params = [TenantContext::id()];

        if ($request->getSearch() !== null) {
            $where .= " AND (CONCAT(U.first_name, ' ', U.last_name) LIKE ?
                          OR CONCAT(P.first_name, ' ', P.last_name) LIKE ?
                          OR P.dni LIKE ?)";
            $like = '%' . $this->db->escapeLikeString($request->getSearch()) . '%';
            array_push($params, $like, $like, $like);
        }

        if ($request->getFrom() !== null) {
            $where .= " AND L.created_at >= ?";
            $params[] = $request->getFrom();
        }

        if ($request->getTo() !== null) {
            $where .= " AND L.created_at < DATE_ADD(?, INTERVAL 1 DAY)";
            $params[] = $request->getTo();
        }

        $total = (int) $this->db->query("SELECT COUNT(*) AS total $from $where", $params)->getRow()->total;

        $pagination = $request->getPagination();
        $rows = $this->db->query(
            "SELECT L.id, L.user_id, L.patient_id, L.evolution_id, L.action, L.created_at,
                    TRIM(CONCAT(U.first_name, ' ', U.last_name)) AS user_name,
                    TRIM(CONCAT(P.first_name, ' ', P.last_name)) AS patient_name
             $from $where
             ORDER BY L.created_at DESC, L.id DESC LIMIT ? OFFSET ?",
            [...$params, $pagination->getLimit(), $pagination->getOffset()]
        )->getResult();

        return [
            'data'  => array_map(fn (object $row) => $this->converter->convert($row), $rows),
            'total' => $total,
        ];
    }
}
