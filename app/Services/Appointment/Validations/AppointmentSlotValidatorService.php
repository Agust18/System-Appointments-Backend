<?php
namespace App\Services\Appointment\Validations;

use App\Exception\Appointment\AppointmentNotPast;
use App\Exception\Appointment\DoctorNotAvailableDayException;
use App\Exception\Appointment\DoctorNotAvailableTimeException;
use App\Models\MedicalSchedule\MedicalScheduleModel;
use App\Models\MedicalService\MedicalServiceModel;
use App\Models\Service\ServiceModel;
use App\Services\Appointment\ServiceDurationCalculatorService;
use DateTime;

/**
 * Reglas del horario de un turno que no dependen de quién lo pide ni de la
 * sesión: fecha no pasada, servicio activo y ofrecido por el médico, y que el
 * bloque completo entre en la agenda del médico.
 *
 * Lo comparten el flujo autenticado (AppointmentCreateValidatorService) y el de
 * reserva pública (PublicAppointmentCreateValidatorService).
 */
final class AppointmentSlotValidatorService
{
    private MedicalScheduleModel $medicalScheduleModel;
    private ServiceModel $serviceModel;
    private MedicalServiceModel $medicalServiceModel;
    private ServiceDurationCalculatorService $durationCalculator;
    private AppointmentServiceOfferValidatorService $serviceOfferValidator;

    public function __construct()
    {
        $this->medicalScheduleModel = new MedicalScheduleModel();
        $this->serviceModel = new ServiceModel();
        $this->medicalServiceModel = new MedicalServiceModel();
        $this->durationCalculator = new ServiceDurationCalculatorService();
        $this->serviceOfferValidator = new AppointmentServiceOfferValidatorService();
    }

    /**
     * @param string $doctorName solo se usa para armar los mensajes de error
     */
    public function validate(int $medicalId, ?int $serviceId, string $fecha, string $hora, string $doctorName): void
    {
        // Validar fecha no pasada
        $now = new DateTime();
        $appointmentDateTime = new DateTime($fecha . ' ' . substr($hora, 0, 5) . ':00');
        if ($appointmentDateTime < $now) {
            throw new AppointmentNotPast;
        }

        // Validar que el servicio sea obligatorio, exista, esté activo y el médico lo ofrezca
        $bloqueTotalMinutes = $this->calculateTotalBlockMinutes($medicalId, $serviceId);

        //Validar que el médico atienda ese día (múltiples rangos posibles)
        $dayOfWeek = (int) date('N', strtotime($fecha));
        $activeRanges = $this->medicalScheduleModel->findActiveRangesByMedicalAndDay($medicalId, $dayOfWeek);

        if (empty($activeRanges)) {
            throw new DoctorNotAvailableDayException($doctorName, $dayOfWeek);
        }

        //Validar que el bloque completo [hora_inicio, hora_fin] quepa en al menos un rango del día
        $horaInicioStr = substr($hora, 0, 5); // 'HH:MM'
        $horaInicioDt = new DateTime("{$fecha} {$horaInicioStr}:00");
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
        $service = $serviceId !== null ? $this->serviceModel->find($serviceId) : null;
        $medicalService = $serviceId !== null
            ? $this->medicalServiceModel->findActiveByMedicalAndService($medicalId, $serviceId)
            : null;

        $this->serviceOfferValidator->validate($service, $medicalService);

        return $this->durationCalculator->calculate($service, $medicalService)->getTotalBlockMinutes();
    }
}
