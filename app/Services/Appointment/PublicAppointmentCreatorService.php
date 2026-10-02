<?php
namespace App\Services\Appointment;

use App\Converter\Appointment\Projection\AppointmentProjectionToPublicAppointmentResponseConverter;
use App\Dto\Request\Appointment\AppointmentRequest;
use App\Dto\Request\Appointment\PublicAppointmentRequest;
use App\Dto\Response\Appointment\PublicAppointmentResponse;
use App\Exception\Appointment\PublicBookingLimitExceededException;
use App\Models\Appointment\AppointmentModel;
use App\Services\Appointment\Projection\AppointmentProjectionFinderService;
use App\Services\Appointment\Validations\PublicAppointmentCreateValidatorService;
use App\Services\Patient\PatientGuestResolverService;
use DateTime;

/**
 * POST /public/appointments: reserva un turno sin cuenta.
 *
 * Los pasos NO se envuelven en una única transacción, a propósito: el lock del
 * booking (AppointmentSlotLockService) se libera en un `finally`, y con una
 * transacción exterior su commit no commitearía y el lock se liberaría antes del
 * commit real, rompiendo la reserva atómica. Cada paso tiene su propio lock + transacción.
 *
 * Si se pierde la carrera por el horario después de resolver la identidad queda un
 * usuario sin cuenta y su paciente sin turno: es inofensivo, el próximo intento con
 * el mismo DNI + teléfono lo reutiliza.
 */
final class PublicAppointmentCreatorService
{
    /** El turno público nace confirmado: sin revisión previa de recepción. */
    private const INITIAL_STATUS = 'confirmed';

    /** Turnos pendientes o confirmados por venir que puede tener un paciente. */
    public const MAX_ACTIVE_FUTURE_APPOINTMENTS = 3;

    private PublicAppointmentCreateValidatorService $validator;
    private PatientGuestResolverService $patientResolver;
    private AppointmentModel $appointmentModel;
    private AppointmentBookingService $bookingService;
    private AppointmentNotificationService $notificationService;
    private AppointmentProjectionFinderService $projectionFinder;
    private AppointmentProjectionToPublicAppointmentResponseConverter $converter;

    public function __construct()
    {
        $this->validator = new PublicAppointmentCreateValidatorService();
        $this->patientResolver = new PatientGuestResolverService();
        $this->appointmentModel = new AppointmentModel();
        $this->bookingService = new AppointmentBookingService();
        $this->notificationService = new AppointmentNotificationService();
        $this->projectionFinder = new AppointmentProjectionFinderService();
        $this->converter = new AppointmentProjectionToPublicAppointmentResponseConverter();
    }

    public function create(PublicAppointmentRequest $request): PublicAppointmentResponse
    {
        // 1. Reglas del turno, antes de tocar users/patients: si fallan no se escribe nada.
        $this->validator->validate($request);

        // 2. Identidad: lock por DNI + transacción propia (casos A–E).
        $patient = $this->patientResolver->resolve($request->getPatient());

        // 3. Tope de turnos futuros activos (límite blando: dos solicitudes simultáneas
        //    del mismo paciente podrían pasarlo por uno).
        if ($this->appointmentModel->countActiveFutureByPatient($patient->getId(), new DateTime())
            >= self::MAX_ACTIVE_FUTURE_APPOINTMENTS) {
            throw new PublicBookingLimitExceededException(self::MAX_ACTIVE_FUTURE_APPOINTMENTS);
        }

        // 4. Reserva atómica del horario (su propio lock + transacción).
        $appointment = $this->bookingService->book(new AppointmentRequest(
            $request->getMedicalId(),
            $patient->getId(),
            $request->getServiceId(),
            $request->getFecha(),
            $request->getHora(),
            $request->getNota(),
            self::INITIAL_STATUS
        ));

        // 5. Aviso al email GUARDADO del paciente (no al tipeado), fuera de toda transacción.
        $this->notificationService->notifyOnCreate($appointment->getId());

        return $this->converter->convert($this->projectionFinder->find($appointment->getId()));
    }
}
