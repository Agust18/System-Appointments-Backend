<?php
namespace App\Services\Evolution;

use App\Converter\Evolution\EvolutionToEvolutionResponseConverter;
use App\Dto\Response\Evolution\EvolutionResponse;
use App\Entity\Evolution\Evolution;
use App\Exception\Evolution\EvolutionNotFoundException;
use App\Models\Evolution\EvolutionModel;

final class EvolutionFinderService
{
    private EvolutionModel $evolutionModel;
    private EvolutionToEvolutionResponseConverter $converter;
    private EvolutionScopeResolverService $scopeResolver;
    private EvolutionAccessLoggerService $accessLogger;

    public function __construct()
    {
        $this->evolutionModel = new EvolutionModel();
        $this->converter = new EvolutionToEvolutionResponseConverter();
        $this->scopeResolver = new EvolutionScopeResolverService();
        $this->accessLogger = new EvolutionAccessLoggerService();
    }

    public function find(int $id): Evolution
    {
        $evolution = $this->evolutionModel->find($id);

        if (!$evolution) {
            throw new EvolutionNotFoundException($id);
        }

        return $evolution;
    }

    /**
     * Para el Controller. Una evolución de otro paciente o de otro médico responde 404 (como si no
     * existiera), para no confirmar que el id existe.
     */
    public function findResponse(int $patientId, int $id, int $roleId, int $userId): EvolutionResponse
    {
        $medicalId = $this->scopeResolver->medicalIdFor($roleId, $userId);
        $evolution = $this->find($id);

        if ($evolution->getPatientId() !== $patientId
            || ($medicalId !== null && $evolution->getMedicalId() !== $medicalId)) {
            throw new EvolutionNotFoundException($id);
        }

        $this->accessLogger->log($userId, $patientId, $id, EvolutionAccessLoggerService::ACTION_VIEW);

        return $this->converter->convert($evolution);
    }
}
