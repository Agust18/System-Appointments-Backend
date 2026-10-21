<?php
namespace App\Services\DayOff\Validations;

use App\Dto\Request\DayOff\DayOffRequest;

final class DayOffCreateValidatorService
{
    private DayOffScopeValidatorService $scopeValidator;

    public function __construct()
    {
        $this->scopeValidator = new DayOffScopeValidatorService();
    }

    public function validate(DayOffRequest $request): void
    {
        $this->scopeValidator->validate($request->getMedicalId(), $request->getDateFrom(), $request->getDateTo());
    }
}
