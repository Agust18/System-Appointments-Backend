<?php
namespace App\Services\Appointment;

use App\Dto\Request\Appointment\AppointmentRequest;
use App\Entity\Appointment\Appointment;
use App\Models\Appointment\AppointmentModel;
use DateTime;

final class AppointmentBookingService
{
    private AppointmentModel $appointmentModel;
    private AppointmentSlotLockService $slotLockService;

    public function __construct()
    {
        $this->appointmentModel = new AppointmentModel();
        $this->slotLockService = new AppointmentSlotLockService();
    }

    public function book(AppointmentRequest $request): Appointment
    {
        return $this->slotLockService->run(
            $request->getMedicalId(),
            $request->getFecha(),
            $request->getHora(),
            $request->getServiceId(),
            null,
            function (DateTime $horaInicio, DateTime $horaFin) use ($request): Appointment {
                $appointment = new Appointment(
                    null,
                    $request->getMedicalId(),
                    $request->getPatientId(),
                    $request->getServiceId(),
                    new DateTime($request->getFecha()),
                    $horaInicio,
                    $horaFin,
                    $request->getNota(),
                    $request->getStatus(),
                    new DateTime(),
                    new DateTime()
                );

                return $this->appointmentModel->insert($appointment);
            }
        );
    }
}
