<?php

namespace App\Services\Permissions;

use App\Constants\Role;
use App\Entity\Appointment\Appointment;
use App\Entity\Medical\Medical;
use App\Models\Appointment\AppointmentModel;
use App\Models\Medical\MedicalModel;

/**
 * Única fuente de las reglas de propiedad: qué puede ver o editar cada rol.
 * Recibe $roleId y $userId explícitos (no lee la sesión) para poder testearse sin mockearla.
 * Qué roles entran a cada endpoint se declara en Routes.php con el filtro `role:...`.
 */
final class PermissionsService
{
    private MedicalModel $medicalModel;
    private AppointmentModel $appointmentModel;

    public function __construct()
    {
        $this->medicalModel = new MedicalModel();
        $this->appointmentModel = new AppointmentModel();
    }

    /** ADMIN y RECEPTION no tienen restricción de propiedad sobre los datos del Tenant. */
    public function hasFullAccess(int $roleId): bool
    {
        return in_array($roleId, [Role::ADMIN, Role::RECEPTION], true);
    }

    public function canViewPatient(int $roleId, int $userId, int $patientId): bool
    {
        if ($this->hasFullAccess($roleId)) {
            return true;
        }

        if ($roleId === Role::MEDICAL) {
            return $this->appointmentModel->hasRelationWithPatient($userId, $patientId);
        }

        return false;
    }

    public function canViewMedical(int $roleId, int $userId, Medical $medical): bool
    {
        if ($this->hasFullAccess($roleId)) {
            return true;
        }

        return $roleId === Role::MEDICAL && $medical->getUserId() === $userId;
    }

    public function canViewAppointment(int $roleId, int $userId, Appointment $appointment): bool
    {
        return $this->hasFullAccess($roleId) || $this->medicalOwnsAppointment($roleId, $userId, $appointment);
    }

    public function canEditAppointment(int $roleId, int $userId, Appointment $appointment): bool
    {
        return $this->hasFullAccess($roleId) || $this->medicalOwnsAppointment($roleId, $userId, $appointment);
    }

    public function canDeleteAppointment(int $roleId): bool
    {
        return $this->hasFullAccess($roleId);
    }

    private function medicalOwnsAppointment(int $roleId, int $userId, Appointment $appointment): bool
    {
        if ($roleId !== Role::MEDICAL) {
            return false;
        }

        $medical = $this->medicalModel->findByUserId($userId);

        return $medical !== null && $appointment->getMedicalId() === $medical->getId();
    }
}
