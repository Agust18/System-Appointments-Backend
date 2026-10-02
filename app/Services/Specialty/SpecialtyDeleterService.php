<?php
namespace App\Services\Specialty;

use App\Exception\Specialty\SpecialtyNotFoundException;
use App\Models\Medical\MedicalModel;
use App\Models\Specialty\SpecialtyModel;
use App\Services\Specialty\SpecialtyFinderService;

final class SpecialtyDeleterService
{
    private SpecialtyModel $specialtyModel;
    private MedicalModel $medicalModel;
    private SpecialtyFinderService $specialtyFinderService;
    public function __construct()
    {
        $this->specialtyModel = new SpecialtyModel();
        $this->medicalModel = new MedicalModel();
        $this->specialtyFinderService = new SpecialtyFinderService();
    }

  public function delete(int $id): void
{
    $this->specialtyFinderService->find($id);
    
    $count = $this->medicalModel->countBySpecialtyId($id);
    
    if ($count > 0) {
        throw new \Exception("No se puede eliminar la especialidad porque está asignada a {$count} médico(s)");
    }
    
    
    $this->specialtyModel->delete($id);
}
}