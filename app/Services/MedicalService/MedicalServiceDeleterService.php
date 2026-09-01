<?php
namespace App\Services\MedicalService;

use App\Models\MedicalService\MedicalServiceModel;

final class MedicalServiceDeleterService
{
    private MedicalServiceModel $medicalServiceModel;

    public function __construct()
    {
        $this->medicalServiceModel = new MedicalServiceModel();
    }

    public function delete(int $medicalId, int $serviceId): void
    {
        $this->medicalServiceModel->delete($medicalId, $serviceId);
    }
}