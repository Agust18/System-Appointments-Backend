<?php
namespace App\Services\DayOff;

use App\Models\DayOff\DayOffModel;

final class DayOffDeleterService
{
    private DayOffModel $dayOffModel;
    private DayOffFinderService $finderService;

    public function __construct()
    {
        $this->dayOffModel = new DayOffModel();
        $this->finderService = new DayOffFinderService();
    }

    public function delete(int $id): void
    {
        $this->finderService->find($id);
        $this->dayOffModel->delete($id);
    }
}
