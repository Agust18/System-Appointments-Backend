<?php
namespace App\Services\DayOff;

use App\Converter\DayOff\DayOffToDayOffSavedResponseConverter;
use App\Dto\Request\DayOff\DayOffRequest;
use App\Dto\Response\DayOff\DayOffSavedResponse;
use App\Entity\DayOff\DayOff;
use App\Models\DayOff\DayOffModel;
use App\Services\DayOff\Validations\DayOffCreateValidatorService;

final class DayOffCreatorService
{
    private DayOffModel $dayOffModel;
    private DayOffToDayOffSavedResponseConverter $converter;
    private DayOffCreateValidatorService $validator;
    private AffectedAppointmentsFinderService $affectedFinder;

    public function __construct()
    {
        $this->dayOffModel = new DayOffModel();
        $this->converter = new DayOffToDayOffSavedResponseConverter();
        $this->validator = new DayOffCreateValidatorService();
        $this->affectedFinder = new AffectedAppointmentsFinderService();
    }

    public function create(DayOffRequest $request): DayOffSavedResponse
    {
        $this->validator->validate($request);

        $dayOff = $this->dayOffModel->insert(DayOff::fromRequest($request, (int) session()->get('userId')));

        // Crear el bloqueo no cancela nada: solo se informan los turnos que quedaron adentro.
        return $this->converter->convert($dayOff, $this->affectedFinder->find($dayOff));
    }
}
