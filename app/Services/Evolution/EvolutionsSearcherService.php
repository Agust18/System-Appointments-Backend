<?php
namespace App\Services\Evolution;

use App\Converter\Evolution\Projection\EvolutionProjectionToEvolutionProjectionResponseConverter;
use App\Dto\Request\PaginationRequest;
use App\Models\Evolution\EvolutionModel;
use App\Services\Patient\PatientFinderService;

final class EvolutionsSearcherService
{
    private EvolutionModel $evolutionModel;
    private EvolutionProjectionToEvolutionProjectionResponseConverter $converter;
    private EvolutionScopeResolverService $scopeResolver;
    private EvolutionAccessLoggerService $accessLogger;
    private PatientFinderService $patientFinder;

    public function __construct()
    {
        $this->evolutionModel = new EvolutionModel();
        $this->converter = new EvolutionProjectionToEvolutionProjectionResponseConverter();
        $this->scopeResolver = new EvolutionScopeResolverService();
        $this->accessLogger = new EvolutionAccessLoggerService();
        $this->patientFinder = new PatientFinderService();
    }

    /**
     * @return array{data: \App\Dto\Response\Evolution\Projection\EvolutionProjectionResponse[], total: int}
     */
    public function search(int $patientId, PaginationRequest $pagination, int $roleId, int $userId): array
    {
        $medicalId = $this->scopeResolver->medicalIdFor($roleId, $userId);
        $this->patientFinder->find($patientId);

        $result = $this->evolutionModel->findByPatient($patientId, $medicalId, $pagination->getPage(), $pagination->getLimit());

        $this->accessLogger->log($userId, $patientId, null, EvolutionAccessLoggerService::ACTION_LIST);

        return [
            'data'  => array_map(fn ($projection) => $this->converter->convert($projection), $result['data']),
            'total' => $result['total'],
        ];
    }
}
