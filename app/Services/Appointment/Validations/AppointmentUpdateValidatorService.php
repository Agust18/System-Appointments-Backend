<?php

namespace App\Services\Appointment\Validations;

use App\Constants\Role;
use App\Dto\Request\Appointment\AppointmentUpdateRequest;
use App\Entity\Appointment\Appointment;
use App\Exception\Appointment\AppointmentNotModifiableException;
use App\Exception\Appointment\AppointmentServiceRequiredException;
use App\Exception\Appointment\InvalidStatusTransitionException;
use App\Exception\Appointment\MedicalCanOnlyChangeStatusException;
use App\Exception\Appointment\MedicalCanOnlyCompleteException;
use App\Exception\Appointment\PastAppointmentCannotBeModifiedException;
use App\Exception\Appointment\PatientCanOnlyCancelException;
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
                $request->getHora() !== null ||
                $request->getNota() !== null
            ) {
                throw new MedicalCanOnlyChangeStatusException();
            }

            if ($requestedStatus !== 'completed') {
                throw new MedicalCanOnlyCompleteException();
            }
        }

        if ($roleId === Role::PATIENT && $requestedStatus !== 'cancelled') {
            throw new PatientCanOnlyCancelException();
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
        $finalServiceId = $request->getServiceId() ?? $appointment->getServiceId();
        if ($finalServiceId === null || $finalServiceId <= 0) {
            throw new AppointmentServiceRequiredException();
        }

    }
}