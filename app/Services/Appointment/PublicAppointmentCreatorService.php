<?php
namespace App\Services\Appointment;

use App\Converter\Appointment\Projection\AppointmentProjectionToPublicAppointmentCreatedResponseConverter;
use App\Dto\Request\Appointment\AppointmentRequest;
use App\Dto\Request\Appointment\PublicAppointmentRequest;
use App\Dto\Response\Appointment\PublicAppointmentCreatedResponse;
use App\Exception\Appointment\PublicBookingLimitExceededException;
use App\Models\Appointment\AppointmentModel;
use App\Services\Appointment\Notification\PublicAppointmentCreatedNotificationService;
use App\Services\Appointment\Projection\AppointmentProjectionFinderService;
use App\Services\Appointment\Validations\PublicAppointmentCreateValidatorService;
use App\Dto\Request\Patient\PatientRequest;
use App\Services\Patient\PatientCreatorService;
use DateTime;

final class PublicAppointmentCreatorService
{
    private const INITIAL_STATUS = 'confirmed';

    public const MAX_ACTIVE_FUTURE_APPOINTMENTS = 3;

    private PublicAppointmentCreateValidatorService $validator;
    private PatientCreatorService $patientCreator;
    private AppointmentModel $appointmentModel;
    private AppointmentBookingService $bookingService;
    private PublicAppointmentCreatedNotificationService $notificationService;
    private AppointmentProjectionFinderService $projectionFinder;
    private AppointmentAccessService $accessService;
    private AppointmentProjectionToPublicAppointmentCreatedResponseConverter $converter;

    public function __construct()
    {
        $this->validator = new PublicAppointmentCreateValidatorService();
        $this->patientCreator = new PatientCreatorService();
        $this->appointmentModel = new AppointmentModel();
        $this->bookingService = new AppointmentBookingService();
        $this->notificationService = new PublicAppointmentCreatedNotificationService();
        $this->projectionFinder = new AppointmentProjectionFinderService();
        $this->accessService = new AppointmentAccessService();
        $this->converter = new AppointmentProjectionToPublicAppointmentCreatedResponseConverter();
    }

    public function create(PublicAppointmentRequest $request): PublicAppointmentCreatedResponse
    {
        $this->validator->validate($request);

        $patient = $this->patientCreator->findOrCreateByDni(PatientRequest::fromGuest($request->getPatient()));

        if ($this->appointmentModel->countActiveFutureByPatient($patient->getId(), new DateTime())
            >= self::MAX_ACTIVE_FUTURE_APPOINTMENTS) {
            throw new PublicBookingLimitExceededException(self::MAX_ACTIVE_FUTURE_APPOINTMENTS);
        }

        $appointment = $this->bookingService->book(new AppointmentRequest(
            $request->getMedicalId(),
            $patient->getId(),
            $request->getServiceId(),
            $request->getFecha(),
            $request->getHora(),
            $request->getNota(),
            self::INITIAL_STATUS
        ));

        // Fuera del lock de reserva: si falla, el turno queda sin código y recepción lo regenera.
        $access = $this->accessService->issue($appointment->getId());

        $this->notificationService->notify($appointment->getId(), $access);

        return $this->converter->convert($this->projectionFinder->find($appointment->getId()), $access);
    }
}
