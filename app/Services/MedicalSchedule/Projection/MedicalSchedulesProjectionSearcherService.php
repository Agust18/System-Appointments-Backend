<?php
namespace App\Services\MedicalSchedule\Projection;

use App\Converter\MedicalSchedule\Projection\MedicalScheduleProjectionToMedicalScheduleProjectionResponseConverter;
use App\Dto\Request\MedicalSchedule\MedicalScheduleFilterRequest;
use App\Models\MedicalSchedule\MedicalScheduleModel;

final class MedicalSchedulesProjectionSearcherService
{
    private MedicalScheduleModel $scheduleModel;
    private MedicalScheduleProjectionToMedicalScheduleProjectionResponseConverter $converter;

    public function __construct()
    {
        $this->scheduleModel = new MedicalScheduleModel();
        $this->converter = new MedicalScheduleProjectionToMedicalScheduleProjectionResponseConverter();
    }

    public function search(MedicalScheduleFilterRequest $request): array
    {
        $entities = $this->scheduleModel->searchProjection($request);
        $total = $this->scheduleModel->count($request);
        
        $responses = [];
        foreach ($entities as $entity) {
            $responses[] = $this->converter->convert($entity);
        }
        
        return [
            'data' => $responses,
            'total' => $total,
        ];
    }
}