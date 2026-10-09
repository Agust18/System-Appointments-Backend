<?php
namespace App\Services\Specialty\Validations;

use App\Dto\Request\Specialty\SpecialtyRequest;
use App\Exception\Specialty\SpecialtyAlreadyExistsException;
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

        $exist = $this->specialtyModel->findByName($name);
        if ($exist) {
            throw new SpecialtyAlreadyExistsException($name);
        }
    }
}