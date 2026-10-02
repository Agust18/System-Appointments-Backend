<?php
namespace App\Controllers\Specialty;

use App\Constants\PublicCatalog;
use App\Dto\Request\PaginationRequest;
use App\Dto\Request\Specialty\SpecialtyFilterRequest;
use App\Services\Specialty\SpecialtiesSearcherService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

/**
 * GET /public/specialties (sin auth). No acepta page/size/search: tope fijo.
 */
final class PublicSpecialtiesGetController extends ResourceController
{
    private SpecialtiesSearcherService $specialtySearcherService;

    public function __construct()
    {
        $this->specialtySearcherService = new SpecialtiesSearcherService();
    }

    public function search(): ResponseInterface
    {
        $request = new SpecialtyFilterRequest(new PaginationRequest(1, PublicCatalog::MAX_ITEMS), null);

        return $this->response->setJSON($this->specialtySearcherService->searchResponses($request));
    }
}
