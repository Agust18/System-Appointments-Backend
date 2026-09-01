<?php
namespace App\Services\Service;

use App\Models\Service\ServiceModel;
use Exception;

final class ServiceDeleterService
{
    private ServiceModel $serviceModel;

    public function __construct()
    {
        $this->serviceModel = new ServiceModel();
    }

    public function delete(int $id): void
    {
        $service = $this->serviceModel->find($id);
        if (!$service) {
            throw new Exception('Servicio no encontrado');
        }

        $this->serviceModel->delete($id);
    }
}