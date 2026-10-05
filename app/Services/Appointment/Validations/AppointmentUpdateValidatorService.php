<?php

namespace App\Services\Appointment\Validations;

use App\Constants\Role;
use App\Dto\Request\Appointment\AppointmentUpdateRequest;
use App\Entity\Appointment\Appointment;
use App\Exception\Appointment\AppointmentServiceRequiredException;
use Exception;

final class AppointmentUpdateValidatorService
{
    public function __construct()
    {
    }
    public function validate(AppointmentUpdateRequest $request, Appointment $appointment, int $id): void
    {
        $roleId = session()->get('roleId');
        $currentStatus = $appointment->getStatus();
        $requestedStatus = $request->getStatus();

        if (in_array($currentStatus, ['completed', 'cancelled'], true)) {
            throw new Exception('No se puede modificar un turno completado o cancelado', 409);
        }

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

            if ($requestedStatus !== 'completed') {
                throw new Exception('Los médicos solo pueden marcar el turno como completado', 403);
            }
        }

        if ($roleId === Role::PATIENT && $requestedStatus !== 'cancelled') {
            throw new Exception('Los pacientes solo pueden cancelar turnos', 403);
        }

        if ($requestedStatus !== null && $requestedStatus !== $currentStatus) {
            $allowedTransitions = [
                'pending' => ['confirmed', 'cancelled'],
                'confirmed' => ['completed', 'cancelled'],
            ];

            if (!in_array($requestedStatus, $allowedTransitions[$currentStatus] ?? [], true)) {
                throw new Exception('La transición de estado del turno no está permitida', 409);
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

        // service_id es nullable en BD y el converter castea NULL a 0: un turno legacy sin servicio llega acá como 0.
        $finalServiceId = $request->getServiceId() ?? $appointment->getServiceId();
        if ($finalServiceId === null || $finalServiceId <= 0) {
            throw new AppointmentServiceRequiredException();
        }

    }
}