<?php
namespace App\Services\MedicalSchedule;

use App\Converter\MedicalSchedule\MedicalScheduleToMedicalScheduleResponseConverter;
use App\Dto\Request\MedicalSchedule\MedicalScheduleUpdateRequest;
use App\Dto\Response\MedicalSchedule\MedicalScheduleResponse;
use App\Models\MedicalSchedule\MedicalScheduleModel;

final class MedicalScheduleUpdaterService
{
    private MedicalScheduleModel $scheduleModel;
    private MedicalScheduleToMedicalScheduleResponseConverter $converter;
    private MedicalScheduleFinderService $finderService;

    public function __construct()
    {
        $this->scheduleModel = new MedicalScheduleModel();
        $this->converter = new MedicalScheduleToMedicalScheduleResponseConverter();
        $this->finderService = new MedicalScheduleFinderService();
    }

    public function update(MedicalScheduleUpdateRequest $request, int $id): MedicalScheduleResponse
    {

        $schedule = $this->finderService->find($id);
        
        $schedule->modify($request);
        
        $updatedSchedule = $this->scheduleModel->update($schedule);
        
        $response =  $this->converter->convert($updatedSchedule);
        
        return $response;


    }
}