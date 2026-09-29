<?php

namespace App\Services\Medical\Validations;

use App\Constants\Role;
use App\Dto\Request\Medical\MedicalDataRequest;
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

    public function validate(int $userId, MedicalDataRequest $request): void
    {

        $user = $this->userFinderService->find($userId);

        if ($user->getRoleId() !== Role::MEDICAL) {
            throw new UserNotMedicalException($userId);
        }

        $existingMedical = $this->medicalModel->findByUserId($userId);
        if ($existingMedical) {
            throw new MedicalUserAlreadyExistsException($userId);
        }

        
        $this->specialtyFinderService->find($request->getSpecialtyId());

        $existingTuition = $this->medicalModel->findByTuition($request->getTuition());
        if ($existingTuition) {
            throw new MedicalTuitionAlreadyExistsException($request->getTuition());
        }
    }
}