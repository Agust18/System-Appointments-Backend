<?php
namespace App\Services\MedicalSchedule;

use App\Converter\MedicalSchedule\MedicalScheduleToMedicalScheduleResponseConverter;
use App\Dto\Request\MedicalSchedule\MedicalScheduleRequest;
use App\Dto\Response\MedicalSchedule\MedicalScheduleResponse;
use App\Entity\MedicalSchedule\MedicalSchedule;
use App\Models\MedicalSchedule\MedicalScheduleModel;
use App\Services\MedicalSchedule\Validations\MedicalScheduleCreateValidatorService;

final class MedicalScheduleCreatorService
{
    private MedicalScheduleModel $scheduleModel;
    private MedicalScheduleToMedicalScheduleResponseConverter $converter;


    private MedicalScheduleCreateValidatorService $validator;

    public function __construct()
    {
        $this->scheduleModel = new MedicalScheduleModel();
        $this->converter = new MedicalScheduleToMedicalScheduleResponseConverter();
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