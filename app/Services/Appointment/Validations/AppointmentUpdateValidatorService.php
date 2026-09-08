<?php

namespace App\Services\Appointment\Validations;

use App\Constants\Role;
use App\Dto\Request\Appointment\AppointmentUpdateRequest;
use App\Entity\Appointment\Appointment;
use App\Exception\Appointment\AppointmentNotFoundException;
use App\Models\Appointment\AppointmentModel;
use App\Services\Appointment\AppointmentFinderService;

final class AppointmentUpdateValidatorService
{
    private AppointmentModel $appointmentModel;
    private AppointmentFinderService $appointmentFinderService;

    public function __construct()
    {
        $this->appointmentModel = new AppointmentModel();
        $this->appointmentFinderService = new AppointmentFinderService();
    }

    public function validate( AppointmentUpdateRequest $request, int $id): Appointment 
    {

        $appointment = $this->appointmentFinderService->find($id);

        if (!$appointment) {
            throw new AppointmentNotFoundException($id);
        }

        $roleId = session()->get('roleId');

        // Médico: solamente puede modificar el estado
        if ($roleId === Role::MEDICAL) {
            if (
                $request->getMedicalId() !== null ||
                $request->getPatientId() !== null ||
                $request->getServiceId() !== null ||
                $request->getFecha() !== null ||
                $request->getHora() !== null ||
                $request->getNota() !== null
            ) {
                throw new \Exception(
                    'Los médicos solo pueden modificar el estado del turno'
                );
            }
        }

        // Validar fecha
        if (
            $request->getFecha() !== null &&
            $request->getFecha() < date('Y-m-d')
        ) {
            throw new \Exception(
                'No se puede modificar un turno con fecha pasada'
            );
        }

        // Valores finales del turno
        $medicalId = $request->getMedicalId()
            ?? $appointment->getMedicalId();

        $fecha = $request->getFecha()
            ?? $appointment->getFecha()->format('Y-m-d');

        $hora = $request->getHora()
            ?? $appointment->getHora()->format('H:i:s');

        // Evitar dos turnos para el mismo médico,
        // fecha y hora
        $existing = $this->appointmentModel->findByMedicalAndDateTime(
            $medicalId,
            $fecha,
            $hora
        );

        if ($existing && $existing->getId() !== $id) {
            throw new \Exception(
                'Ya existe un turno para ese médico a esa hora'
            );
        }

        return $appointment;
    }
}