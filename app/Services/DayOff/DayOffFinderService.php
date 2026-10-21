<?php
namespace App\Services\DayOff;

use App\Converter\DayOff\DayOffToDayOffResponseConverter;
use App\Dto\Response\DayOff\DayOffResponse;
use App\Entity\DayOff\DayOff;
use App\Exception\DayOff\DayOffNotFoundException;
use App\Models\DayOff\DayOffModel;

final class DayOffFinderService
{
    private DayOffModel $dayOffModel;
    private DayOffToDayOffResponseConverter $converter;

    public function __construct()
    {
        $this->dayOffModel = new DayOffModel();
        $this->converter = new DayOffToDayOffResponseConverter();
    }

    public function find(int $id): DayOff
    {
        $dayOff = $this->dayOffModel->find($id);

        if (!$dayOff) {
            throw new DayOffNotFoundException($id);
        }

        return $dayOff;
    }

    public function findResponse(int $id): DayOffResponse
    {
        return $this->converter->convert($this->find($id));
    }
}
