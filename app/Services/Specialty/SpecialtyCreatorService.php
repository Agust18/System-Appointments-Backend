<?php
namespace App\Services\Specialty;

use App\Converter\Specialty\SpecialtyToSpecialtyResponseConverter;
use App\Dto\Request\Specialty\SpecialtyRequest;
use App\Dto\Response\Specialty\SpecialtyResponse;
use App\Entity\Specialty\Specialty;
use App\exception\Specialty\SpecialtyAlreadyExistsException;
use App\Models\Specialty\SpecialtyModel;
use App\Services\Specialty\Validations\SpecialtiesCreateValidatorService;

final class SpecialtyCreatorService
{
    private SpecialtyModel $specialtyModel;
    private SpecialtyToSpecialtyResponseConverter $converter;
    private SpecialtiesCreateValidatorService $validator;

    public function __construct()
    {
        $this->specialtyModel = new SpecialtyModel();
        
        $this->converter = new SpecialtyToSpecialtyResponseConverter();
        
        $this->validator = new SpecialtiesCreateValidatorService();
        
    }

    public function create(SpecialtyRequest $request): SpecialtyResponse
    {
        $this->validator->validate($request); 
        
        $specialty = Specialty::fromRequest($request); 
        
        $newSpecialty = $this->specialtyModel->insert($specialty);
        
        $specialtyResponse = $this->converter->convert($newSpecialty);
        
        return $specialtyResponse;
    }
}