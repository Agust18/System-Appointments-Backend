<?php
namespace App\Services\Medical;

use App\Converter\Medical\MedicalToMedicalResponseConverter;
use App\Dto\Response\Medical\MedicalResponse;
use App\Entity\Medical\Medical;
use App\Exception\Medical\MedicalNotFoundException;
use App\Exception\Medical\MedicalNotFoundExceptionByUserException;
use App\Models\Medical\MedicalModel;

final class MedicalFinderService
{
    private MedicalModel $medicalModel;
    private MedicalToMedicalResponseConverter $converter;

    public function __construct()
    {
        $this->medicalModel = new MedicalModel();
        $this->converter = new MedicalToMedicalResponseConverter();
    }

    public function find(int $id): Medical
    {
        $medical = $this->medicalModel->find($id);
        
        if (!$medical) {
            throw new MedicalNotFoundException($id);
        }
        
        return $medical;
    }

    public function findByUserId(int $userId): MedicalResponse
    {
        $medical = $this->medicalModel->findByUserId($userId);
        
        if (!$medical) {
             throw new MedicalNotFoundExceptionByUserException($userId);
        }
        
        return $this->converter->convert($medical);
    }
} 