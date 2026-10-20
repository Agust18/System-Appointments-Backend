<?php
namespace App\Controllers\Appointment;

use App\Dto\Request\Appointment\PublicAppointmentAccessRequest;
use App\Services\Appointment\AppointmentAccessService;
use App\Support\IdentityNormalizer;
use App\Validation\RequestValidator;
use CodeIgniter\RESTful\ResourceController;

/**
 * Base de los controllers de `/consulta`: ambos reciben las mismas credenciales (DNI + código, o token).
 */
abstract class PublicAppointmentAccessController extends ResourceController
{
    protected function getAccessRequest(): PublicAppointmentAccessRequest
    {
        $parameters = IdentityNormalizer::normalizeInput($this->request->getJSON(true) ?? []);

        RequestValidator::validate($parameters, PublicAppointmentAccessRequest::rules(), PublicAppointmentAccessRequest::messages());

        $token = trim((string) ($parameters['token'] ?? ''));
        $code = trim((string) ($parameters['code'] ?? ''));
        $dni = trim((string) ($parameters['dni'] ?? ''));

        return new PublicAppointmentAccessRequest(
            $dni === '' ? null : (int) $dni,
            $code === '' ? null : AppointmentAccessService::normalizeCode($code),
            $token === '' ? null : $token
        );
    }
}
