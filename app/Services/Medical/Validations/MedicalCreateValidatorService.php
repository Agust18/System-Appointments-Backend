<?php

namespace App\Services\Medical\Validations;

use App\Constants\Role;
use App\Dto\Request\Medical\MedicalRequest;
use App\Exception\Medical\MedicalTuitionAlreadyExistsException;
use App\Exception\Medical\MedicalUserAlreadyExistsException;
use App\Exception\Medical\UserNotMedicalException;
use App\Models\Medical\MedicalModel;
use App\Services\Specialty\SpecialtyFinderService;
use App\Services\User\UserFinderService;

final class MedicalCreateValidatorService
{
    private MedicalModel $medicalModel;
    private UserFinderService $userFinderService;
    private SpecialtyFinderService $specialtyFinderService;

    public function __construct()
    {
        $this->medicalModel = new MedicalModel();
        $this->userFinderService = new UserFinderService();
        $this->specialtyFinderService = new SpecialtyFinderService();
    }

    public function validate(MedicalRequest $request): void
    {
    
        $user = $this->userFinderService->find($request->getUserId());

        if ($user->getRoleId() !== Role::MEDICAL) {
            throw new UserNotMedicalException($request->getUserId());
        }

        $existingMedical = $this->medicalModel->findByUserId($request->getUserId());
        if ($existingMedical) {
            throw new MedicalUserAlreadyExistsException($request->getUserId());
        }

        
        $this->specialtyFinderService->find($request->getSpecialtyId());

        $existingTuition = $this->medicalModel->findByTuition($request->getTuition());
        if ($existingTuition) {
            throw new MedicalTuitionAlreadyExistsException($request->getTuition());
        }
    }
}