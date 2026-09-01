<?php
namespace App\Services\MedicalSchedule;

use App\Converter\MedicalSchedule\MedicalScheduleToMedicalScheduleResponseConverter;
use App\Dto\Response\MedicalSchedule\MedicalScheduleResponse;
use App\Entity\MedicalSchedule\MedicalSchedule;
use App\Exception\MedicalSchedule\MedicalScheduleNotFoundException;
use App\Models\MedicalSchedule\MedicalScheduleModel;

final class MedicalScheduleFinderService
{
    private MedicalScheduleModel $scheduleModel;
    private MedicalScheduleToMedicalScheduleResponseConverter $converter;

    public function __construct()
    {
        $this->scheduleModel = new MedicalScheduleModel();
        $this->converter = new MedicalScheduleToMedicalScheduleResponseConverter();
    }

    public function find(int $id): MedicalSchedule
    {
        $schedule = $this->scheduleModel->find($id);
        
        if (!$schedule) {
            throw new MedicalScheduleNotFoundException($id);
        }
        
        return $schedule;
    }

    public function findResponse(int $id): MedicalScheduleResponse
    {
        $schedule = $this->find($id);
        $response =$this->converter->convert($schedule);
        return $response;
    }

    public function findByMedicalId(int $medicalId): array
    {
        $schedules = $this->scheduleModel->findByMedicalId($medicalId);
        $responses = [];
        foreach ($schedules as $schedule) {
            $responses[] = $this->converter->convert($schedule);
        }
        return $responses;
    }
}