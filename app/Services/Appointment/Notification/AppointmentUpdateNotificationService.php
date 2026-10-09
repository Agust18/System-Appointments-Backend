<?php

namespace App\Services\Appointment\Notification;

use App\Dto\Request\Appointment\AppointmentUpdateRequest;
use App\Entity\Appointment\Appointment;

/**
 * Decide qué aviso corresponde a una edición. Cancelado y confirmado tienen
 * prioridad; si no cambió el estado, avisa solo cuando fecha, hora o médico cambiaron.
 */
final class AppointmentUpdateNotificationService
{
    private AppointmentCancelledNotificationService $cancelled;
    private AppointmentConfirmedNotificationService $confirmed;
    private AppointmentRescheduledNotificationService $rescheduled;

    public function __construct(?callable $sender = null)
    {
        $this->cancelled   = new AppointmentCancelledNotificationService($sender);
        $this->confirmed   = new AppointmentConfirmedNotificationService($sender);
        $this->rescheduled = new AppointmentRescheduledNotificationService($sender);
    }

    /**
     * @param Appointment $before Estado previo a la edición (sin modificar).
     */
    public function notify(Appointment $before, AppointmentUpdateRequest $request, int $appointmentId): void
    {
        $newStatus = $request->getStatus();

        if ($newStatus === 'cancelled' && $before->getStatus() !== 'cancelled') {
            $this->cancelled->notify($appointmentId);
            return;
        }

        if ($newStatus === 'confirmed' && $before->getStatus() !== 'confirmed') {
            $this->confirmed->notify($appointmentId);
            return;
        }

        if ($this->wasRescheduled($before, $request)) {
            $this->rescheduled->notify($appointmentId);
        }
    }

    /**
     * Se compara normalizado: el front puede reenviar "09:00" y la base guarda "09:00:00".
     */
    private function wasRescheduled(Appointment $before, AppointmentUpdateRequest $request): bool
    {
        return ($request->getFecha() !== null && substr($request->getFecha(), 0, 10) !== $before->getFecha()->format('Y-m-d'))
            || ($request->getHora() !== null && substr($request->getHora(), 0, 5) !== $before->getHora()->format('H:i'))
            || ($request->getMedicalId() !== null && $request->getMedicalId() !== $before->getMedicalId());
    }
}
