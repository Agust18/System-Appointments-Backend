<?php

namespace App\Services\Appointment\Validations;

use App\Constants\Role;
use App\Dto\Request\Appointment\AppointmentUpdateRequest;
use App\Entity\Appointment\Appointment;
use Exception;

final class AppointmentUpdateValidatorService
{
    public function __construct()
    {
    }
    public function validate(AppointmentUpdateRequest $request, Appointment $appointment, int $id): void
    {
        $roleId = session()->get('roleId');

        if ($roleId === Role::MEDICAL) {
            if (
                $request->getMedicalId() !== null ||
                $request->getPatientId() !== null ||
                $request->getServiceId() !== null ||
                $request->getFecha() !== null ||
                $request->getHora() !== null ||
                $request->getNota() !== null
            ) {
                throw new Exception('Los médicos solo pueden modificar el estado del turno', 403);
            }

            if ($request->getStatus() !== null && $request->getStatus() !== 'completed') {
                throw new Exception('Los médicos solo pueden marcar el turno como completado', 403);
            }
        }

        if (
            $request->getFecha() !== null &&
            $request->getFecha() < date('Y-m-d')
        ) {
            throw new Exception(
                'No se puede modificar un turno con fecha pasada',
                409
            );
        }

    }
}