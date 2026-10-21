<?php
namespace App\Models\Evolution;

use App\Tenancy\TenantContext;
use CodeIgniter\Database\BaseConnection;
use Config\Database;

final class EvolutionAccessLogModel
{
    private BaseConnection $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function insert(int $userId, int $patientId, ?int $evolutionId, string $action): void
    {
        $this->db->query(
            "INSERT INTO evolution_access_log (tenant_id, user_id, patient_id, evolution_id, action)
             VALUES (?, ?, ?, ?, ?)",
            [TenantContext::id(), $userId, $patientId, $evolutionId, $action]
        );
    }
}
