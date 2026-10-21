<?php
namespace App\Services\DayOff\Validations;

use App\Exception\DayOff\DayOffOverlapException;
use App\Exception\Validation\ValidationException;
use App\Models\DayOff\DayOffModel;
use App\Services\Medical\MedicalFinderService;

/**
 * Reglas de un bloqueo ya con sus valores finales (alta o edición): rango de fechas coherente,
 * médico existente en el Tenant y sin solaparse con otro bloqueo del mismo alcance.
 */
final class DayOffScopeValidatorService
{
    private DayOffModel $dayOffModel;
    private MedicalFinderService $medicalFinderService;

    public function __construct()
    {
        $this->dayOffModel = new DayOffModel();
        $this->medicalFinderService = new MedicalFinderService();
    }

    public function validate(?int $medicalId, string $dateFrom, string $dateTo, ?int $excludeId = null): void
    {
        if ($dateTo < $dateFrom) {
            throw new ValidationException(['dateTo' => 'La fecha de fin no puede ser anterior a la fecha de inicio.']);
        }

        if ($medicalId !== null) {
            $this->medicalFinderService->find($medicalId);
        }

        if ($this->dayOffModel->findOverlapping($medicalId, $dateFrom, $dateTo, $excludeId)) {
            throw new DayOffOverlapException($medicalId === null);
        }
    }
}
