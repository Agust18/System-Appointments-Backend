<?php
namespace App\Controllers\MedicalService;

use App\Services\MedicalService\Projection\PublicMedicalServicesFinderService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

/**
 * GET /public/medicals/{id}/services (sin auth).
 */
final class PublicMedicalServicesGetController extends ResourceController
{
    private PublicMedicalServicesFinderService $finder;

    public function __construct()
    {
        $this->finder = new PublicMedicalServicesFinderService();
    }

    public function findByMedical(int $medicalId): ResponseInterface
    {
        return $this->response->setJSON($this->finder->findByMedicalResponses($medicalId));
    }
}
