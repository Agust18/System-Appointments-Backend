<?php

namespace App\Services\MedicalSchedule\Validations;

use App\Dto\Request\MedicalSchedule\MedicalScheduleRequest;
use App\Exception\MedicalSchedule\InvalidScheduleTimeException;
use App\Exception\MedicalSchedule\MedicalNotActiveException;
use App\Exception\MedicalSchedule\ScheduleAlreadyExistsException;
use App\Exception\MedicalSchedule\ScheduleOverlapException;
use App\Models\MedicalSchedule\MedicalScheduleModel;
use App\Services\Medical\MedicalFinderService;
use App\Services\User\UserFinderService;

final class MedicalScheduleCreateValidatorService
{
    private MedicalScheduleModel $scheduleModel;
    private MedicalFinderService $medicalFinderService;
    private UserFinderService $userFinderService;

    public function __construct()
    {
        $this->scheduleModel = new MedicalScheduleModel();
        $this->medicalFinderService = new MedicalFinderService();
        $this->userFinderService = new UserFinderService();
    }

    public function validate(MedicalScheduleRequest $request): void
    {
        $medical = $this->medicalFinderService->find($request->getMedicalId());

        $dayOfWeek = $request->getDayOfWeek();

        if ($request->getStartTime() >= $request->getEndTime()) {
            throw new InvalidScheduleTimeException();
        }

        if (!$medical->getIsActive()) {
            throw new MedicalNotActiveException($request->getMedicalId());
        }

        $conflict = $this->scheduleModel->findOverlapping(
            $request->getMedicalId(),
            $dayOfWeek,
            $request->getStartTime(),
            $request->getEndTime()
        );

        if ($conflict) {
            if (
                $conflict->getStartTime() === $request->getStartTime() &&
                $conflict->getEndTime() === $request->getEndTime()
            ) {
                throw new ScheduleAlreadyExistsException();
            }

            $user = $this->userFinderService->find($medical->getUserId());
            throw new ScheduleOverlapException($user->getFullName(), $dayOfWeek);
        }
    }
}