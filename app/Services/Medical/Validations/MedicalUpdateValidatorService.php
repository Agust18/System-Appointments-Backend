<?php

namespace App\Services\Medical\Validations;

use App\Dto\Request\Medical\MedicalUpdateRequest;
use App\Entity\Medical\Medical;
use App\Exception\Medical\MedicalTuitionAlreadyExistsException;
use App\Models\Medical\MedicalModel;
use App\Services\Specialty\SpecialtyFinderService;

final class MedicalUpdateValidatorService
{
    private MedicalModel $medicalModel;
    private SpecialtyFinderService $specialtyFinderService;

    public function __construct()
    {
        $this->medicalModel = new MedicalModel();
        $this->specialtyFinderService = new SpecialtyFinderService();
    }

    public function validate(MedicalUpdateRequest $request, Medical $medical): void
    {
      
        if (
            $request->getSpecialtyId() &&
            $request->getSpecialtyId() !== $medical->getSpecialtyId()
        ) {
            $this->specialtyFinderService->find($request->getSpecialtyId());
        }

       
        if (
             $request->getTuition() &&
            $request->getTuition() !== $medical->getTuition()
        ) {
            $existingTuition = $this->medicalModel->findByTuition($request->getTuition());

            if ($existingTuition && $existingTuition->getId() !== $medical->getId()) {
                throw new MedicalTuitionAlreadyExistsException($request->getTuition());
            }
        }
    }
}