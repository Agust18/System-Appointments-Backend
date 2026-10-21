<?php
namespace App\Services\Evolution;

use App\Converter\Evolution\EvolutionToEvolutionResponseConverter;
use App\Dto\Request\PaginationRequest;
use App\Models\Evolution\EvolutionModel;
use App\Services\Patient\PatientFinderService;

final class EvolutionsSearcherService
{
    private EvolutionModel $evolutionModel;
    private EvolutionToEvolutionResponseConverter $converter;
    private EvolutionScopeResolverService $scopeResolver;
    private EvolutionAccessLoggerService $accessLogger;
    private PatientFinderService $patientFinder;

    public function __construct()
    {
        $this->evolutionModel = new EvolutionModel();
        $this->converter = new EvolutionToEvolutionResponseConverter();
        $this->scopeResolver = new EvolutionScopeResolverService();
        $this->accessLogger = new EvolutionAccessLoggerService();
        $this->patientFinder = new PatientFinderService();
    }

    /**
     * @return array{data: \App\Dto\Response\Evolution\EvolutionResponse[], total: int}
     */
    public function search(int $patientId, PaginationRequest $pagination, int $roleId, int $userId): array
    {
        $medicalId = $this->scopeResolver->medicalIdFor($roleId, $userId);
        $this->patientFinder->find($patientId);

        $result = $this->evolutionModel->findByPatient($patientId, $medicalId, $pagination->getPage(), $pagination->getLimit());

        $this->accessLogger->log($userId, $patientId, null, EvolutionAccessLoggerService::ACTION_LIST);

        return [
            'data'  => array_map(fn ($evolution) => $this->converter->convert($evolution), $result['data']),
            'total' => $result['total'],
        ];
    }
}
