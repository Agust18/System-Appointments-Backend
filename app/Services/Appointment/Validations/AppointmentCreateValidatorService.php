<?php
namespace App\Services\Appointment\Validations;

use App\Constants\Role;
use App\Dto\Request\Appointment\AppointmentRequest;
use App\Exception\Appointment\DoctorNotAvailableDayException;
use App\Exception\Appointment\DoctorNotAvailableTimeException;
use App\Models\MedicalSchedule\MedicalScheduleModel;
use App\Models\MedicalService\MedicalServiceModel;
use App\Models\Service\ServiceModel;
use App\Services\Medical\MedicalFinderService;
use App\Services\Patient\PatientFinderService;
use App\Services\User\UserFinderService;
use DateTime;

final class AppointmentCreateValidatorService
{
    private MedicalScheduleModel $medicalScheduleModel;
    private MedicalFinderService $medicalFinderService;
    private UserFinderService $userFinderService;
    private PatientFinderService $patientFinderService;
    private ServiceModel $serviceModel;
    private MedicalServiceModel $medicalServiceModel;

    public function __construct()
    {
        $this->medicalScheduleModel = new MedicalScheduleModel();
        $this->medicalFinderService = new MedicalFinderService();
        $this->userFinderService = new UserFinderService();
        $this->patientFinderService = new PatientFinderService();
        $this->serviceModel = new ServiceModel();
        $this->medicalServiceModel = new MedicalServiceModel();
    }

    public function validate(AppointmentRequest $request): void
    {
        $userRoleId = session()->get('roleId');
        if ($userRoleId === Role::MEDICAL) {
            throw new \Exception('Los médicos no pueden crear turnos');
        }
        if ($userRoleId === Role::PATIENT) {
            if ($request->getStatus() !== 'pending') {
                throw new \Exception('Los pacientes solo pueden crear turnos con estado "Pendiente"');
            }
        }
        if ($userRoleId === Role::RECEPTION) {
            if ($request->getStatus() !== 'pending') {
                throw new \Exception('Los recepcionistas solo pueden crear turnos con estado "Pendiente"');
            }
        }

        // 1. Validar que el médico exista
        $medical = $this->medicalFinderService->find($request->getMedicalId());
        if (!$medical) {
            throw new \Exception('El médico no existe');
        }
        $user = $this->userFinderService->find($medical->getUserId());
        $doctorName = $user ? $user->getUsername() : 'Médico';

        // 2. Validar paciente existente y rol
        $patient = $this->patientFinderService->find($request->getPatientId());
        if (!$patient) {
            throw new \Exception('El paciente no existe');
        }

        $patientUser = $this->userFinderService->find($patient->getUserId());
        if ($patientUser->getRoleId() !== Role::PATIENT) {
            throw new \Exception('El usuario seleccionado no tiene rol paciente');
        }

        // 3. Validar que el paciente no sea el mismo que el médico
        if ($patientUser->getId() === $user->getId()) {
            throw new \Exception('El paciente no puede ser el mismo que el médico');
        }

        // 4. Validar fecha no pasada
        $today = new DateTime();
        $appointmentDate = new DateTime($request->getFecha());
        $appointmentDate->setTime(0, 0);
        $today->setTime(0, 0);

        if ($appointmentDate < $today) {
            throw new \Exception('No se pueden crear turnos en fechas pasadas');
        }

        // 5. Validar que el servicio sea obligatorio, exista, esté activo y el médico lo ofrezca
        $bloqueTotalMinutes = $this->calculateTotalBlockMinutes($request->getMedicalId(), $request->getServiceId());

        // 6. Validar que el médico atienda ese día (múltiples rangos posibles)
        $dayOfWeek = (int) date('N', strtotime($request->getFecha()));
        $activeRanges = $this->medicalScheduleModel->findActiveRangesByMedicalAndDay(
            $request->getMedicalId(),
            $dayOfWeek
        );

        if (empty($activeRanges)) {
            throw new DoctorNotAvailableDayException($doctorName, $dayOfWeek);
        }

        // 7. Validar que el bloque completo [hora_inicio, hora_fin] quepa en al menos un rango del día
        $horaInicioStr = substr($request->getHora(), 0, 5); // 'HH:MM'
        $horaInicioDt = new DateTime("{$request->getFecha()} {$horaInicioStr}:00");
        $horaFinDt = (clone $horaInicioDt)->modify("+{$bloqueTotalMinutes} minutes");
        $horaFinStr = $horaFinDt->format('H:i');

        $fitsInRange = false;
        $rangosTexto = [];
        foreach ($activeRanges as $range) {
            $rangoInicio = substr($range->getStartTime(), 0, 5);
            $rangoFin = substr($range->getEndTime(), 0, 5);
            $rangosTexto[] = "{$rangoInicio} - {$rangoFin}";

            if ($horaInicioStr >= $rangoInicio && $horaFinStr <= $rangoFin) {
                $fitsInRange = true;
                break;
            }
        }

        if (!$fitsInRange) {
            throw new DoctorNotAvailableTimeException(
                $doctorName,
                implode(', ', $rangosTexto),
                $horaInicioStr
            );
        }
    }

    private function calculateTotalBlockMinutes(int $medicalId, ?int $serviceId): int
    {
        if ($serviceId === null || $serviceId <= 0) {
            throw new \Exception('Debe seleccionar un servicio para agendar el turno');
        }

        $service = $this->serviceModel->find($serviceId);
        if (!$service || !$service->isActive()) {
            throw new \Exception('El servicio seleccionado no existe o no se encuentra activo');
        }

        // Validar que el médico ofrezca este servicio de forma activa
        $medicalService = $this->medicalServiceModel->findActiveByMedicalAndService($medicalId, $serviceId);
        if (!$medicalService) {
            throw new \Exception('El profesional seleccionado no ofrece el servicio indicado o se encuentra inactivo');
        }

        $effectiveDuration = ($medicalService->getCustomDurationMinutes() !== null)
            ? $medicalService->getCustomDurationMinutes()
            : $service->getDurationMinutes();

        return $effectiveDuration + $service->getBufferMinutes();
    }
}