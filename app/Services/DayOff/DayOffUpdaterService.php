<?php
namespace App\Services\DayOff;

use App\Converter\DayOff\DayOffToDayOffSavedResponseConverter;
use App\Dto\Request\DayOff\DayOffUpdateRequest;
use App\Dto\Response\DayOff\DayOffSavedResponse;
use App\Models\DayOff\DayOffModel;
use App\Services\DayOff\Validations\DayOffUpdateValidatorService;

final class DayOffUpdaterService
{
    private DayOffModel $dayOffModel;
    private DayOffToDayOffSavedResponseConverter $converter;
    private DayOffFinderService $finderService;
    private DayOffUpdateValidatorService $validator;
    private AffectedAppointmentsFinderService $affectedFinder;

    public function __construct()
    {
        $this->dayOffModel = new DayOffModel();
        $this->converter = new DayOffToDayOffSavedResponseConverter();
        $this->finderService = new DayOffFinderService();
        $this->validator = new DayOffUpdateValidatorService();
        $this->affectedFinder = new AffectedAppointmentsFinderService();
    }

    public function update(DayOffUpdateRequest $request, int $id): DayOffSavedResponse
    {
        $dayOff = $this->finderService->find($id);

        $dayOff->modify($request);

        $this->validator->validate($dayOff);

        $updated = $this->dayOffModel->update($dayOff);

        return $this->converter->convert($updated, $this->affectedFinder->find($updated));
    }
}
