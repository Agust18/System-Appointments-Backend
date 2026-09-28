<?php
namespace App\Services\Appointment\Validations;

use App\Constants\Role;
use App\Dto\Request\Appointment\AppointmentRequest;
use App\Exception\Appointment\AppointmentNotPast;
use App\Exception\Appointment\CannotCreateAppointmentForOtherPatientException;
use App\Exception\Appointment\DoctorNotAvailableDayException;
use App\Exception\Appointment\DoctorNotAvailableTimeException;
use App\Exception\Appointment\InvalidInitialStatusException;
use App\Exception\Appointment\MedicalCannotCreateAppointmentException;
use App\Exception\Appointment\MedicalServiceNotOfferedException;
use App\Exception\Appointment\PatientCannotBeMedicalException;
use App\Exception\Appointment\ServiceNotAvailableException;
use App\Exception\Patient\UserNotPatientException;
use App\Models\MedicalSchedule\MedicalScheduleModel;
use App\Models\MedicalService\MedicalServiceModel;
use App\Services\Medical\MedicalFinderService;
use App\Services\Patient\PatientFinderService;
use App\Services\Service\ServiceFinderService;
use App\Services\User\UserFinderService;
use DateTime;

final class AppointmentCreateValidatorService
{
    private MedicalScheduleModel $medicalScheduleModel;
    private MedicalFinderService $medicalFinderService;
    private UserFinderService $userFinderService;
    private PatientFinderService $patientFinderService;
    private ServiceFinderService $finder;
    private MedicalServiceModel $medicalServiceModel;

    public function __construct()
    {
        $this->medicalScheduleModel = new MedicalScheduleModel();
        $this->medicalFinderService = new MedicalFinderService();
        $this->userFinderService = new UserFinderService();
        $this->patientFinderService = new PatientFinderService();
        $this->finder = new ServiceFinderService();
        $this->medicalServiceModel = new MedicalServiceModel();
    }

    public function validate(AppointmentRequest $request): void
    {

        $userRoleId = session()->get('roleId');
        if ($userRoleId === Role::MEDICAL) {
            throw new MedicalCannotCreateAppointmentException();
        }
        if ($userRoleId === Role::PATIENT) {
            if ($request->getStatus() !== 'pending') {
                throw new InvalidInitialStatusException('pacientes');
            }
        }
        if ($userRoleId === Role::RECEPTION) {
            if ($request->getStatus() !== 'pending') {
                throw new InvalidInitialStatusException('recepcionistas');
            }
        }

        $medical = $this->medicalFinderService->find($request->getMedicalId());    
        
        $user = $this->userFinderService->find($medical->getUserId());
        
        $doctorName = $user ? $user->getUsername() : 'Profesional';

        $patient = $this->patientFinderService->find($request->getPatientId());
       
        $patientUser = $this->userFinderService->find($patient->getUserId());

        if ($userRoleId === Role::PATIENT) {
            $currentUserId = session()->get('userId');

            if ($patient->getUserId() !== $currentUserId) {
                throw new CannotCreateAppointmentForOtherPatientException();
            }
        }
        if ($patientUser->getRoleId() !== Role::PATIENT) {
            throw new UserNotPatientException();
        }

        // Validar que el paciente no sea el mismo que el médico
        if ($patientUser->getId() === $user->getId()) {
            throw new PatientCannotBeMedicalException();
        }

        // Validar fecha no pasada
        $now = new DateTime();
        $appointmentDateTime = new DateTime(
            $request->getFecha() . ' ' . substr($request->getHora(), 0, 5) . ':00'
        );
        if ($appointmentDateTime < $now) {
            throw new AppointmentNotPast;
        }

        // Validar que el servicio sea obligatorio, exista, esté activo y el médico lo ofrezca
        $bloqueTotalMinutes = $this->calculateTotalBlockMinutes($request->getMedicalId(), $request->getServiceId());

        //Validar que el médico atienda ese día (múltiples rangos posibles)
        $dayOfWeek = (int) date('N', strtotime($request->getFecha()));
        $activeRanges = $this->medicalScheduleModel->findActiveRangesByMedicalAndDay(
            $request->getMedicalId(),
            $dayOfWeek
        );

        if (empty($activeRanges)) {
            throw new DoctorNotAvailableDayException($doctorName, $dayOfWeek);
        }

        //Validar que el bloque completo [hora_inicio, hora_fin] quepa en al menos un rango del día
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
        $service = $this->finder->find($serviceId);

        if (!$service->isActive()) {
            throw new ServiceNotAvailableException('El servicio seleccionado no se encuentra activo');
        }
        // Validar que el médico ofrezca este servicio de forma activa
        $medicalService = $this->medicalServiceModel->findActiveByMedicalAndService($medicalId, $serviceId);
        if (!$medicalService) {
            throw new MedicalServiceNotOfferedException();
        }

        $effectiveDuration = ($medicalService->getCustomDurationMinutes() !== null)
            ? $medicalService->getCustomDurationMinutes()
            : $service->getDurationMinutes();

        return $effectiveDuration + $service->getBufferMinutes();
    }
}