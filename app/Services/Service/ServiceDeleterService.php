<?php
namespace App\Services\Service;

use App\Exception\Service\ServiceNotFoundException;
use App\Models\Service\ServiceModel;
use Exception;
use App\Services\Service\ServiceFinderService;

final class ServiceDeleterService
{
    private ServiceModel $serviceModel;
    private ServiceFinderService $serviceFinderService;

    public function __construct()
    {
        $this->serviceModel = new ServiceModel();
        $this->serviceFinderService = new ServiceFinderService();
    }

    public function delete(int $id): void
    {
        $this->serviceFinderService->find($id);
        
        $this->serviceModel->delete($id);
    }
}