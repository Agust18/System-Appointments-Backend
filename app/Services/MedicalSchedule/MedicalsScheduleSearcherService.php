<?php
namespace App\Services\MedicalSchedule;

use App\Converter\MedicalSchedule\MedicalScheduleToMedicalScheduleResponseConverter;
use App\Dto\Request\MedicalSchedule\MedicalScheduleFilterRequest;
use App\Models\MedicalSchedule\MedicalScheduleModel;

final class MedicalsScheduleSearcherService
{
    private MedicalScheduleModel $scheduleModel;
    private MedicalScheduleToMedicalScheduleResponseConverter $converter;

    public function __construct()
    {
        $this->scheduleModel = new MedicalScheduleModel();
        $this->converter = new MedicalScheduleToMedicalScheduleResponseConverter();
    }

    public function search(MedicalScheduleFilterRequest $request): array
    {
        $entities = $this->scheduleModel->search($request);
        
        $responses = [];
        foreach ($entities as $entity) {
            $responses[] = $this->converter->convert($entity);
        }
        
        return $responses;
    }
}