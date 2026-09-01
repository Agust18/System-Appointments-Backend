<?php
namespace App\Services\Medical\Projection;


use App\Converter\Medical\Projection\MedicalProjectionToMedicalProjectionResponseConverter;
use App\Dto\Response\Medical\Projection\MedicalProjectionResponse;
use App\Entity\Medical\Projection\MedicalProjection;
use App\Exception\Medical\MedicalNotFoundException;
use App\Exception\Medical\MedicalNotFoundExceptionByUserException;
use App\Models\Medical\MedicalModel;

final class MedicalProjectionFinderService
{
    private MedicalModel $medicalModel;
    private MedicalProjectionToMedicalProjectionResponseConverter $converter;

    public function __construct()
    {
        $this->medicalModel = new MedicalModel();
        $this->converter = new MedicalProjectionToMedicalProjectionResponseConverter();
    }

   
    public function find(int $id): MedicalProjection
    {
        $medical = $this->medicalModel->findProjection($id);
        
        if (!$medical) {
            throw new MedicalNotFoundException($id);
        }
        
        return $medical;
    }

    
    public function findResponse(int $id): MedicalProjectionResponse
    {
        $medical = $this->find($id);
        return $this->converter->convert($medical);
    }

    
    public function findByUserIdResponse(int $userId): MedicalProjectionResponse
    {
        $medical = $this->medicalModel->findByUserIdProjection($userId);
        
        if (!$medical) {
            throw new MedicalNotFoundExceptionByUserException($userId);
        }
        
        return $this->converter->convert($medical);
    }
}