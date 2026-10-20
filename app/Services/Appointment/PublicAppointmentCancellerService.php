<?php
namespace App\Services\Appointment;

use App\Converter\Appointment\Projection\AppointmentProjectionToPublicAppointmentResponseConverter;
use App\Dto\Request\Appointment\PublicAppointmentAccessRequest;
use App\Dto\Response\Appointment\PublicAppointmentResponse;
use App\Exception\Appointment\AppointmentNotCancellableException;
use App\Models\Appointment\AppointmentModel;
use App\Services\Appointment\Notification\PublicAppointmentCancelledNotificationService;
use DateTime;

/**
 * Cancelación del paciente desde `/consulta`: turnos pendientes o confirmados que todavía no ocurrieron.
 * Sin plazo mínimo en v1. No toma el lock de reserva: cancelar solo libera el horario.
 */
final class PublicAppointmentCancellerService
{
    private const CANCELLABLE_STATUSES = ['pending', 'confirmed'];

    private PublicAppointmentAccessResolverService $resolver;
    private AppointmentModel $appointmentModel;
    private PublicAppointmentCancelledNotificationService $notificationService;
    private AppointmentProjectionToPublicAppointmentResponseConverter $converter;

    public function __construct()
    {
        $this->resolver = new PublicAppointmentAccessResolverService();
        $this->appointmentModel = new AppointmentModel();
        $this->notificationService = new PublicAppointmentCancelledNotificationService();
        $this->converter = new AppointmentProjectionToPublicAppointmentResponseConverter();
    }

    /**
     * @throws AppointmentNotCancellableException
     */
    public function cancel(PublicAppointmentAccessRequest $request): PublicAppointmentResponse
    {
        $projection = $this->resolver->resolve($request);

        $start = new DateTime($projection->getFecha()->format('Y-m-d') . ' ' . $projection->getHora()->format('H:i:s'));
        if (!in_array($projection->getStatus(), self::CANCELLABLE_STATUSES, true) || $start < new DateTime()) {
            throw new AppointmentNotCancellableException();
        }

        // Si otra cancelación se adelantó entre el chequeo y el UPDATE, no se cancela (ni se avisa) dos veces.
        if (!$this->appointmentModel->cancelIfActive($projection->getId())) {
            throw new AppointmentNotCancellableException();
        }

        $this->notificationService->notify($projection->getId());

        return $this->converter->convert($this->appointmentModel->findProjection($projection->getId()));
    }
}
