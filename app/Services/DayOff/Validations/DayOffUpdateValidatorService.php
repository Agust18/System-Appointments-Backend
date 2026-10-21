<?php
namespace App\Services\DayOff\Validations;

use App\Entity\DayOff\DayOff;

final class DayOffUpdateValidatorService
{
    private DayOffScopeValidatorService $scopeValidator;

    public function __construct()
    {
        $this->scopeValidator = new DayOffScopeValidatorService();
    }

    /**
     * Se llama con el bloqueo ya modificado (valores finales), antes de persistirlo.
     */
    public function validate(DayOff $dayOff): void
    {
        $this->scopeValidator->validate(
            $dayOff->getMedicalId(),
            $dayOff->getDateFrom(),
            $dayOff->getDateTo(),
            $dayOff->getId()
        );
    }
}
