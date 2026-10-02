<?php
namespace App\Services\Service;

use App\Constants\PublicCatalog;
use App\Converter\Service\ServiceToPublicServiceResponseConverter;
use App\Dto\Request\PaginationRequest;
use App\Dto\Request\Service\ServiceFilterRequest;
use App\Dto\Response\Service\PublicServiceResponse;
use App\Models\Service\ServiceModel;

/**
 * Catálogo público de servicios: siempre solo los activos y con tope fijo,
 * sin que el cliente pueda elegir el filtro ni la paginación.
 */
final class PublicServicesSearcherService
{
    private ServiceModel $serviceModel;
    private ServiceToPublicServiceResponseConverter $converter;

    public function __construct()
    {
        $this->serviceModel = new ServiceModel();
        $this->converter = new ServiceToPublicServiceResponseConverter();
    }

    /**
     * @return array{data: PublicServiceResponse[], total: int}
     */
    public function searchResponses(): array
    {
        $request = new ServiceFilterRequest(new PaginationRequest(1, PublicCatalog::MAX_ITEMS), null, true);

        $responses = [];
        foreach ($this->serviceModel->search($request) as $service) {
            $responses[] = $this->converter->convert($service);
        }

        return [
            'data' => $responses,
            'total' => $this->serviceModel->count($request),
        ];
    }
}
