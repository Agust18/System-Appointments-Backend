<?php
namespace App\Services\Appointment\Validations;

use App\Exception\Appointment\AppointmentInPastException;
use App\Exception\Appointment\DoctorNotAvailableException;
use App\Models\MedicalSchedule\MedicalScheduleModel;
use App\Services\Appointment\ServiceBlockResolverService;
use DateTime;


final class AppointmentSlotValidatorService
{
    private MedicalScheduleModel $medicalScheduleModel;
    private ServiceBlockResolverService $blockResolver;

    public function __construct()
    {
        $this->medicalScheduleModel = new MedicalScheduleModel();
        $this->blockResolver = new ServiceBlockResolverService();
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
            throw new AppointmentInPastException();
        }

        // Validar que el servicio sea obligatorio, exista, esté activo y el médico lo ofrezca
        $bloqueTotalMinutes = $this->blockResolver->resolve($medicalId, $serviceId)->getTotalBlockMinutes();

        //Validar que el médico atienda ese día (múltiples rangos posibles)
        $dayOfWeek = (int) date('N', strtotime($fecha));
        $activeRanges = $this->medicalScheduleModel->findActiveRangesByMedicalAndDay($medicalId, $dayOfWeek);

        if (empty($activeRanges)) {
            throw DoctorNotAvailableException::onDay($doctorName, $dayOfWeek);
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
            throw DoctorNotAvailableException::outsideHours(
                $doctorName,
                implode(', ', $rangosTexto),
                $horaInicioStr
            );
        }
    }
}
