<?php
namespace App\Controllers\Medical;

use App\Constants\PublicCatalog;
use App\Dto\Request\Medical\MedicalFilterRequest;
use App\Dto\Request\PaginationRequest;
use App\Services\Medical\Projection\MedicalAvailableSearcherService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

/**
 * GET /public/medicals/available?specialtyId= (sin auth). Solo médicos activos (médico y
 * usuario); la response solo trae id, nombre y especialidad. Sin page/size/search.
 */
final class PublicMedicalsGetController extends ResourceController
{
    private MedicalAvailableSearcherService $searcher;

    public function __construct()
    {
        $this->searcher = new MedicalAvailableSearcherService();
    }

    public function search(): ResponseInterface
    {
        $specialtyId = $this->request->getGet('specialtyId');

        $request = new MedicalFilterRequest(
            new PaginationRequest(1, PublicCatalog::MAX_ITEMS),
            ($specialtyId !== null && $specialtyId !== '') ? (int) $specialtyId : null,
            true,
            null
        );

        return $this->response->setJSON($this->searcher->searchResponses($request));
    }
}
