<?php

namespace App\Services\User\Validations;

use App\Entity\User\User;
use App\Models\User\UserModel;
use App\Models\Medical\MedicalModel;
use App\Models\Appointment\AppointmentModel;

final class UserDeleterValidatorService
{
    private UserModel $userModel;
    private MedicalModel $medicalModel;
    private AppointmentModel $appointmentModel;

    public function __construct()
    {
        $this->userModel        = new UserModel();
        $this->medicalModel     = new MedicalModel();
        $this->appointmentModel = new AppointmentModel();
    }

    public function validate(int $id, User $user): void
    {
        if ($user->getRoleId() === 1) {
            $adminCount = $this->userModel->countByRoleId(1);
            if ($adminCount <= 1) {
                throw new \Exception('No se puede eliminar al último administrador del sistema', 409);
            }
        }


        if ($this->medicalModel->findByUserId($id)) {
            throw new \Exception('No se puede eliminar el usuario porque tiene un perfil de médico asociado', 409);
        }


        $appointmentsAsMedical = $this->appointmentModel->findByMedicalUserId($id);
        if (!empty($appointmentsAsMedical)) {
            $count = count($appointmentsAsMedical);
            throw new \Exception("No se puede eliminar el usuario porque tiene {$count} turnos como médico", 409);
        }
    }
}
