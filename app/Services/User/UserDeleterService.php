<?php

namespace App\Services\User;

use App\exception\User\UserNotFoundException;
use App\Models\User\UserModel;
use App\Models\Patient\PatientModel;
use App\Models\Medical\MedicalModel;
use App\Models\Appointment\AppointmentModel;

final class UserDeleterService
{

    private UserModel $userModel;
    private UserFinderService $userFinderService;
    private PatientModel $patientModel;
    private MedicalModel $medicalModel;
    private AppointmentModel $appointmentModel;


    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->userFinderService = new UserFinderService();
        $this->patientModel = new PatientModel();
        $this->medicalModel = new MedicalModel();
        $this->appointmentModel = new AppointmentModel();
    }

    public function delete(int $id): void
    {
        $user = $this->userFinderService->find($id);
        if (!$user) {
            throw new UserNotFoundException($id);
        }
        if ($user->getRoleId() === 1) {
            $adminCount = $this->userModel->countByRoleId(1);
            if ($adminCount <= 1) {
                throw new \Exception('No se puede eliminar al último administrador del sistema');
            }
        }

        $patient = $this->patientModel->findByUserId($id);
        if ($patient) {
            throw new \Exception('No se puede eliminar el usuario porque tiene un perfil de paciente asociado');
        }

        $medical = $this->medicalModel->findByUserId($id);
        if ($medical) {
            throw new \Exception('No se puede eliminar el usuario porque tiene un perfil de médico asociado');
        }

        // 5. Validar que no tenga turnos como paciente
        $appointmentsAsPatient = $this->appointmentModel->findByPatientUserId($id);
        if (!empty($appointmentsAsPatient)) {
            $count = count($appointmentsAsPatient);
            throw new \Exception("No se puede eliminar el usuario porque tiene {$count} turnos como paciente");
        }

        // 6. Validar que no tenga turnos como médico
        $appointmentsAsMedical = $this->appointmentModel->findByMedicalUserId($id);
        if (!empty($appointmentsAsMedical)) {
            $count = count($appointmentsAsMedical);
            throw new \Exception("No se puede eliminar el usuario porque tiene {$count} turnos como médico");
        }

        $this->userModel->delete($id);
    }
}