<?php
namespace App\Services\MedicalSchedule;

use App\Converter\MedicalSchedule\MedicalScheduleToMedicalScheduleResponseConverter;
use App\Dto\Request\MedicalSchedule\MedicalScheduleRequest;
use App\Dto\Response\MedicalSchedule\MedicalScheduleResponse;
use App\Entity\MedicalSchedule\MedicalSchedule;
use App\Exception\MedicalSchedule\InvalidScheduleTimeException;
use App\Exception\MedicalSchedule\ScheduleOverlapException;
use App\Models\MedicalSchedule\MedicalScheduleModel;
use App\Services\Medical\MedicalFinderService;
use App\Services\User\UserFinderService;
use App\Services\MedicalSchedule\Validations\MedicalScheduleCreateValidatorService;

final class MedicalScheduleCreatorService
{
    private MedicalScheduleModel $scheduleModel;
    private MedicalScheduleToMedicalScheduleResponseConverter $converter;
    private MedicalFinderService $medicalFinderService;
    private UserFinderService $userFinderService;

    private MedicalScheduleCreateValidatorService $validator;

    public function __construct()
    {
        $this->scheduleModel = new MedicalScheduleModel();
        $this->converter = new MedicalScheduleToMedicalScheduleResponseConverter();
        $this->medicalFinderService = new MedicalFinderService();
        $this->userFinderService = new UserFinderService();
        $this->validator = new MedicalScheduleCreateValidatorService();
    }

   public function create(MedicalScheduleRequest $request): MedicalScheduleResponse
{
    $this->validator->validate($request);
    
    $schedule = MedicalSchedule::fromRequest($request);
    
    $newSchedule = $this->scheduleModel->insert($schedule);

    return $this->converter->convert($newSchedule);
}
}