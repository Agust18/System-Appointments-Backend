<?php
namespace App\Services\Appointment;

use App\Dto\Request\Appointment\PublicAppointmentAccessRequest;
use App\Entity\Appointment\Projection\AppointmentProjection;
use App\Exception\Patient\PublicBookingIdentityException;
use App\Models\Appointment\AppointmentModel;
use App\Services\Patient\PublicIdentityAttemptLimiter;

/**
 * Resuelve el turno de `/consulta` a partir de DNI + código o del token del link.
 *
 * Todo fallo es el mismo PublicBookingIdentityException (404 neutral): DNI inexistente, código incorrecto,
 * token desconocido y DNI bloqueado se ven igual. El DNI se bloquea tras MAX_FAILURES intentos fallidos
 * (PublicIdentityAttemptLimiter); el token no tiene DNI, lo cubre el throttle por IP.
 */
final class PublicAppointmentAccessResolverService
{
    private AppointmentAccessService $accessService;
    private AppointmentModel $appointmentModel;
    private PublicIdentityAttemptLimiter $limiter;

    public function __construct()
    {
        $this->accessService = new AppointmentAccessService();
        $this->appointmentModel = new AppointmentModel();
        $this->limiter = new PublicIdentityAttemptLimiter();
    }

    /**
     * @throws PublicBookingIdentityException
     */
    public function resolve(PublicAppointmentAccessRequest $request): AppointmentProjection
    {
        $token = $request->getToken();

        if ($token !== null && $token !== '') {
            return $this->projectionOrFail($this->accessService->findIdByToken($token));
        }

        $dni = $request->getDni();
        $code = $request->getCode();
        if ($dni === null || $code === null) {
            throw new PublicBookingIdentityException();
        }

        if ($this->limiter->isBlocked($dni)) {
            throw new PublicBookingIdentityException();
        }

        $appointmentId = $this->accessService->findIdByDniAndCode($dni, $code);
        if ($appointmentId === null) {
            $this->limiter->recordFailure($dni);
            throw new PublicBookingIdentityException();
        }

        $this->limiter->clear($dni);

        return $this->projectionOrFail($appointmentId);
    }

    private function projectionOrFail(?int $appointmentId): AppointmentProjection
    {
        $projection = $appointmentId === null ? null : $this->appointmentModel->findProjection($appointmentId);
        if ($projection === null) {
            throw new PublicBookingIdentityException();
        }

        return $projection;
    }
}
