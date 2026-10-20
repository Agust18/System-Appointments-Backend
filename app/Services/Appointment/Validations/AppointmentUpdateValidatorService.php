<?php

namespace App\Services\Appointment\Validations;

use App\Constants\Role;
use App\Dto\Request\Appointment\AppointmentUpdateRequest;
use App\Entity\Appointment\Appointment;
use App\Exception\Appointment\AppointmentActionForbiddenException;
use App\Exception\Appointment\AppointmentNotModifiableException;
use App\Exception\Appointment\AppointmentServiceRequiredException;
use App\Exception\Appointment\InvalidStatusTransitionException;
use App\Exception\Appointment\PastAppointmentCannotBeModifiedException;
use App\Exception\Validation\ValidationException;
use App\Services\User\UserFinderService;

final class AppointmentUpdateValidatorService
{
    private AppointmentMedicalActiveValidatorService $medicalActiveValidator;

    public function __construct()
    {
        $this->medicalActiveValidator = new AppointmentMedicalActiveValidatorService();
    }
    public function validate(AppointmentUpdateRequest $request, Appointment $appointment, int $id): void
    {
        $this->validateScheduleFieldsNotBlank($request);

        $roleId = session()->get('roleId');
        $currentStatus = $appointment->getStatus();
        $requestedStatus = $request->getStatus();

        if (in_array($currentStatus, ['completed', 'cancelled'], true)) {
            throw new AppointmentNotModifiableException();
        }

        if ($roleId === Role::MEDICAL) {
            if (
                $request->getMedicalId() !== null ||
                $request->getPatientId() !== null ||
                $request->getServiceId() !== null ||
                $request->getFecha() !== null ||
                $request->getHora() !== null
            ) {
                throw AppointmentActionForbiddenException::medicalCanOnlyChangeStatus();
            }

            if (!in_array($requestedStatus, ['completed', 'cancelled'], true)) {
                throw AppointmentActionForbiddenException::medicalCanOnlyComplete();
            }
        }

        if ($requestedStatus !== null && $requestedStatus !== $currentStatus) {
            $allowedTransitions = [
                'pending' => ['confirmed', 'cancelled'],
                'confirmed' => ['completed', 'cancelled'],
            ];

            if (!in_array($requestedStatus, $allowedTransitions[$currentStatus] ?? [], true)) {
                throw new InvalidStatusTransitionException();
            }
        }

        if (
            $request->getFecha() !== null &&
            $request->getFecha() < date('Y-m-d')
        ) {
            throw new PastAppointmentCannotBeModifiedException();
        }

        // Reasignar el turno a otro médico: su usuario tiene que estar activo (como en el alta).
        if ($request->getMedicalId() !== null && $request->getMedicalId() !== $appointment->getMedicalId()) {
            $this->medicalActiveValidator->validate($request->getMedicalId());
        }

        // service_id es nullable en BD y el converter castea NULL a 0: un turno legacy sin servicio llega acá como 0.
        $finalServiceId = $request->getServiceId();
        if ($finalServiceId === null) {
            $finalServiceId = $appointment->getServiceId();
        }
        if ($finalServiceId <= 0) {
            throw new AppointmentServiceRequiredException();
        }

    }

    /**
     * fecha/hora ausentes (null) significan "sin cambio"; presentes pero vacías no: llegarían a
     * reschedule() y romperían el cálculo del bloque (500). El formato de las no vacías lo valida la regla del DTO.
     */
    private function validateScheduleFieldsNotBlank(AppointmentUpdateRequest $request): void
    {
        $errors = [];
        if ($request->getFecha() === '') {
            $errors['fecha'] = 'La fecha no puede estar vacía.';
        }
        if ($request->getHora() === '') {
            $errors['hora'] = 'La hora no puede estar vacía.';
        }
        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }
}
