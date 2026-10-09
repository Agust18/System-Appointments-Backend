<?php

namespace App\Services\Patient;

use App\Constants\Role;
use App\Dto\Request\PaginationRequest;
use App\Dto\Request\Patient\PatientFilterRequest;

final class PatientFilterBuilderService
{
    public function build(PaginationRequest $pagination, ?string $search): PatientFilterRequest
    {
        $roleId = session()->get('roleId');
        $userId = session()->get('userId');

        $medicalUserId = $roleId === Role::MEDICAL ? $userId : null;

        return new PatientFilterRequest($pagination, $search, $medicalUserId);
    }
}
