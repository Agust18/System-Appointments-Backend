<?php
namespace App\Services\Appointment;

use App\Dto\Request\Appointment\AppointmentUpdateRequest;
use App\Entity\Appointment\Appointment;
use App\Models\Appointment\AppointmentModel;
use DateTime;

final class AppointmentUpdateBookingService
{
    private AppointmentModel $appointmentModel;
    private AppointmentSlotLockService $slotLockService;

    public function __construct()
    {
        $this->appointmentModel = new AppointmentModel();
        $this->slotLockService = new AppointmentSlotLockService();
    }

    public function update(Appointment $appointment, AppointmentUpdateRequest $request, int $id): Appointment
    {
        // Valores finales: los que vengan en el request pisan a los actuales.
        $finalMedicalId = $request->getMedicalId() ?? $appointment->getMedicalId();
        $finalServiceId = $request->getServiceId() ?? $appointment->getServiceId();
        $finalFecha = $request->getFecha() ?? $appointment->getFecha()->format('Y-m-d');
        $finalHora = $request->getHora() ?? $appointment->getHora()->format('H:i:s');

        // Recalcula bloque y verifica solapamiento excluyendo el propio turno que se edita.
        return $this->slotLockService->run(
            $finalMedicalId,
            $finalFecha,
            $finalHora,
            $finalServiceId,
            $id,
            function (DateTime $horaInicio, DateTime $horaFin) use ($appointment, $request): Appointment {
                $appointment->modify($request);
                $appointment->setHoraFin($horaFin);

                return $this->appointmentModel->update($appointment);
            }
        );
    }
}
