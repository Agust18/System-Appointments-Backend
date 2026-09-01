<?php
namespace App\Services\MedicalSchedule\Validations;

use App\Dto\Request\MedicalSchedule\MedicalScheduleRequest;
use App\Exception\Medical\MedicalNotFoundException;
use App\Exception\MedicalSchedule\InvalidDayOfWeekException;
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

        $medicalId = $request->getMedicalId();

        if ($medicalId === null || $medicalId <= 0) {
            throw new \Exception('Debe seleccionar un médico');
        }

        $medical = $this->medicalFinderService->find($request->getMedicalId());

        if (!$medical) {
            throw new MedicalNotFoundException($request->getMedicalId());
        }

        


        $dayOfWeek = $request->getDayOfWeek();
        if ($dayOfWeek < 1 || $dayOfWeek > 7) {
            throw new InvalidDayOfWeekException($dayOfWeek);
        }

        if ($request->getStartTime() >= $request->getEndTime()) {
            throw new InvalidScheduleTimeException();
        }

        $medical = $this->medicalFinderService->find($request->getMedicalId());
        if (!$medical->getIsActive()) {
            throw new MedicalNotActiveException($request->getMedicalId());
        }

        $user = $this->userFinderService->find($medical->getUserId());
        $medicalName = $user->getUsername();

        $existingSchedule = $this->scheduleModel->findExisting(
            $request->getMedicalId(),
            $request->getDayOfWeek(),
            $request->getStartTime(),
            $request->getEndTime()
        );



        if ($existingSchedule) {
            throw new ScheduleAlreadyExistsException();
        }

        $conflict = $this->scheduleModel->findOverlapping(
            $request->getMedicalId(),
            $request->getDayOfWeek(),
            $request->getStartTime(),
            $request->getEndTime()
        );

        if ($conflict) {
            throw new ScheduleOverlapException($medicalName, $request->getDayOfWeek());
        }







    }
}