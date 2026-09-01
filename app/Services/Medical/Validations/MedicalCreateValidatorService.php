<?php
namespace App\Services\Medical\Validations;

use App\Dto\Request\Medical\MedicalRequest;
use App\Exception\Medical\MedicalTuitionAlreadyExistsException;
use App\Exception\Medical\MedicalUserAlreadyExistsException;
use App\Exception\Specialty\SpecialtyNotFoundException;
use App\Exception\User\UserNotFoundException;
use App\Exception\User\UserNotMedicalException;
use App\Models\Medical\MedicalModel;
use App\Services\User\UserFinderService;
use App\Services\Specialty\SpecialtyFinderService;

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
        if (!$user) {
            throw new UserNotFoundException($request->getUserId());
        }

     
        if ($user->getRoleId() !== 2) {
            throw new UserNotMedicalException($request->getUserId());
        }

       
        $existingMedical = $this->medicalModel->findByUserId($request->getUserId());
        if ($existingMedical) {
            throw new MedicalUserAlreadyExistsException($request->getUserId());
        }

        
        $specialty = $this->specialtyFinderService->find($request->getSpecialtyId());
        if (!$specialty) {
            throw new SpecialtyNotFoundException($request->getSpecialtyId());
        }

       
        $existingTuition = $this->medicalModel->findByTuition($request->getTuition());
        if ($existingTuition) {
            throw new MedicalTuitionAlreadyExistsException($request->getTuition());
        }
    }
}