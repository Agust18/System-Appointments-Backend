<?php
namespace App\Services\MedicalSchedule\Projection;

use App\Converter\MedicalSchedule\Projection\MedicalScheduleProjectionToMedicalScheduleProjectionResponseConverter;
use App\Dto\Response\MedicalSchedule\MedicalScheduleResponse;
use App\Dto\Response\MedicalSchedule\Projection\MedicalScheduleProjectionResponse;
use App\Entity\MedicalSchedule\Projection\MedicalScheduleProjection;
use App\Exception\MedicalSchedule\MedicalScheduleNotFoundException;
use App\Models\MedicalSchedule\MedicalScheduleModel;

final class MedicalScheduleProjectionFinderService
{
    private MedicalScheduleModel $scheduleModel;
    private MedicalScheduleProjectionToMedicalScheduleProjectionResponseConverter $converter;

    public function __construct()
    {
        $this->scheduleModel = new MedicalScheduleModel();
        $this->converter = new MedicalScheduleProjectionToMedicalScheduleProjectionResponseConverter();
    }

    public function find(int $id): MedicalScheduleProjection
    {
        $schedule = $this->scheduleModel->findProjection($id);
        
        if (!$schedule) {
            throw new MedicalScheduleNotFoundException($id);
        }
        
        return $schedule;
    }

    public function findResponse(int $id): MedicalScheduleProjectionResponse
    {
        $schedule = $this->find($id);
        $response =$this->converter->convert($schedule);
        return $response;
    }

    public function findByMedicalId(int $medicalId): array
    {
        $schedules = $this->scheduleModel->findByMedicalIdProjection($medicalId);
        $responses = [];
        foreach ($schedules as $schedule) {
            $responses[] = $this->converter->convert($schedule);
        }
        return $responses;
    }
}