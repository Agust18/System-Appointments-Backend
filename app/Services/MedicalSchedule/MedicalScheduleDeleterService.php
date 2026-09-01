<?php
namespace App\Services\MedicalSchedule;

use App\Models\MedicalSchedule\MedicalScheduleModel;

final class MedicalScheduleDeleterService
{
    private MedicalScheduleModel $scheduleModel;
    private MedicalScheduleFinderService $finderService;

    public function __construct()
    {
        $this->scheduleModel = new MedicalScheduleModel();
        $this->finderService = new MedicalScheduleFinderService();
    }

    public function delete(int $id): void
    {
        $this->finderService->find($id);
        $this->scheduleModel->delete($id);
    }
}