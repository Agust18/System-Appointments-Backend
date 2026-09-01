<?php
namespace App\Services\Specialty\Validations;

use App\Dto\Request\Specialty\SpecialtyRequest;
use App\Exception\Specialty\SpecialtyAlreadyExistsException;
use App\Exception\Specialty\SpecialtyNameRequiredException;
use App\Exception\Specialty\SpecialtyNameTooShortException;
use App\Exception\Specialty\SpecialtyNameTooLongException;
use App\Models\Specialty\SpecialtyModel;

final class SpecialtiesCreateValidatorService
{
    private SpecialtyModel $specialtyModel;

    public function __construct()
    {
        $this->specialtyModel = new SpecialtyModel();
    }

    public function validate(SpecialtyRequest $request): void
    {
        $name = $request->getName();

        if (empty($name)) {
            throw new SpecialtyNameRequiredException();
        }

      
        if (strlen($name) < 3) {
            throw new SpecialtyNameTooShortException();
        }

        
        if (strlen($name) > 50) {
            throw new SpecialtyNameTooLongException();
        }

       
        $exist = $this->specialtyModel->findByName($name);
        if ($exist) {
            throw new SpecialtyAlreadyExistsException($name);
        }
    }
}