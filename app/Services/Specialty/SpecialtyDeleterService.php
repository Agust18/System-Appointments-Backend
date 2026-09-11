<?php
namespace App\Services\Specialty;

use App\exception\Specialty\SpecialtyNotFoundException;
use App\Models\Medical\MedicalModel;
use App\Models\Specialty\SpecialtyModel;

final class SpecialtyDeleterService
{
    private SpecialtyModel $specialtyModel;
    private MedicalModel $medicalModel;
    public function __construct()
    {
        $this->specialtyModel = new SpecialtyModel();
        $this->medicalModel = new MedicalModel();
    }

  public function delete(int $id): void
{
  
    $specialty = $this->specialtyModel->find($id);
    if (!$specialty) {
        throw new SpecialtyNotFoundException($id);
    }

    $count = $this->medicalModel->countBySpecialtyId($id);
    if ($count > 0) {
        throw new \Exception("No se puede eliminar la especialidad porque está asignada a {$count} médico(s)");
    }
    
    
    $this->specialtyModel->delete($id);
}
}