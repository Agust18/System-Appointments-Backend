<?php
namespace App\Services\Evolution;

use App\Models\Evolution\EvolutionAccessLogModel;

/**
 * Registra cada lectura de evoluciones. No atrapa fallos: si el registro no se puede guardar,
 * la lectura falla (los datos clínicos no se entregan sin dejar rastro).
 */
final class EvolutionAccessLoggerService
{
    public const ACTION_LIST = 'list';
    public const ACTION_VIEW = 'view';

    private EvolutionAccessLogModel $logModel;

    public function __construct()
    {
        $this->logModel = new EvolutionAccessLogModel();
    }

    public function log(int $userId, int $patientId, ?int $evolutionId, string $action): void
    {
        $this->logModel->insert($userId, $patientId, $evolutionId, $action);
    }
}
