<?php

namespace App\Services\Permissions;

use App\Constants\Role;
use App\Entity\Appointment\Appointment;
use App\Models\Appointment\AppointmentModel;
use App\Models\Medical\MedicalModel;
use App\Models\Patient\PatientModel;

final class PermissionsService
{
    private PatientModel $patientModel;
    private MedicalModel $medicalModel;
    private AppointmentModel $appointmentModel;

    public function __construct()
    {
        $this->patientModel = new PatientModel();
        $this->medicalModel = new MedicalModel();
        $this->appointmentModel = new AppointmentModel();
    }

    public function canViewPatient(int $roleId, int $userId, int $patientId): bool
    {
        if (in_array($roleId, [Role::ADMIN, Role::RECEPTION], true)) {
            return true;
        }

        if ($roleId === Role::PATIENT) {
            $patient = $this->patientModel->find($patientId);
            return $patient !== null && $patient->getUserId() === $userId;
        }

        if ($roleId === Role::MEDICAL) {
            return $this->appointmentModel->hasRelationWithPatient($userId, $patientId);
        }

        return false;
    }

    public function canViewAppointment(int $roleId, int $userId, Appointment $appointment): bool
    {
        if (in_array($roleId, [Role::ADMIN, Role::RECEPTION], true)) {
            return true;
        }

        if ($roleId === Role::PATIENT) {
            $patient = $this->patientModel->findByUserId($userId);
            return $patient !== null && $appointment->getPatientId() === $patient->getId();
        }

        if ($roleId === Role::MEDICAL) {
            $medical = $this->medicalModel->findByUserId($userId);
            return $medical !== null && $appointment->getMedicalId() === $medical->getId();
        }

        return false;
    }

    public function canEditAppointment(int $roleId, int $userId, Appointment $appointment): bool
    {
        if (in_array($roleId, [Role::ADMIN, Role::RECEPTION], true)) {
            return true;
        }

        if ($roleId === Role::MEDICAL) {
            $medical = $this->medicalModel->findByUserId($userId);
            return $medical !== null && $appointment->getMedicalId() === $medical->getId();
        }

        return false;
    }

    public function canCancelAppointment(int $roleId, int $userId, Appointment $appointment): bool
    {
        if (in_array($roleId, [Role::ADMIN, Role::RECEPTION], true)) {
            return true;
        }

        if ($roleId === Role::PATIENT) {
            $patient = $this->patientModel->findByUserId($userId);
            return $patient !== null
                && $appointment->getPatientId() === $patient->getId()
                && $appointment->getStatus() === 'pending';
        }

        return false;
    }

    public function canDeleteAppointment(int $roleId): bool
    {
        return in_array($roleId, [Role::ADMIN, Role::RECEPTION], true);
    }
}
