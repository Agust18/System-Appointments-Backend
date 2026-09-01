<?php
namespace App\Services\Specialty;

use App\Converter\Specialty\SpecialtyToSpecialtyResponseConverter;
use App\Dto\Response\Specialty\SpecialtyResponse;
use App\exception\Specialty\SpecialtyNotFoundException;
use App\Models\Specialty\SpecialtyModel;
Use App\Entity\Specialty\Specialty;

final class SpecialtyFinderService
{
    private SpecialtyModel $specialtyModel;
    private SpecialtyToSpecialtyResponseConverter $converter;

    public function __construct()
    {
        $this->specialtyModel = new SpecialtyModel();
        $this->converter = new SpecialtyToSpecialtyResponseConverter();
    }

    public function find(int $id):Specialty
    {
        $specialty = $this->specialtyModel->find($id);
        
        if (!$specialty) {
            throw new SpecialtyNotFoundException($id);
        }
        
        return $specialty;
    }

    public function findResponse(int $id): SpecialtyResponse
{
    $specialty = $this->find($id);
    return $this->converter->convert($specialty);
}

    
   
} 