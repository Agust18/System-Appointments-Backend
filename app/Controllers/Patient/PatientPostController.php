<?php
namespace App\Controllers\Patient;

use App\Dto\Request\Patient\PatientDataRequest;
use App\Dto\Request\Patient\PatientRequest;
use App\Dto\Request\User\UserRequest;
use App\Services\Patient\PatientCreatorService;
use App\Support\IdentityNormalizer;
use App\Validation\IdentityRules;
use App\Validation\RequestValidator;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

final class PatientPostController extends ResourceController
{
    private PatientCreatorService $patientCreatorService;

    public function __construct()
    {
        $this->patientCreatorService = new PatientCreatorService();
    }

    public function create(): ResponseInterface
    {
        $patientRequest = $this->getRequest();
        $patientResponse = $this->patientCreatorService->create($patientRequest, true);

        return $this->response->setJSON($patientResponse);
    }

    private function getRequest(): PatientRequest
    {
        $parameters = $this->request->getJSON(true) ?? [];
        $parameters['user'] = IdentityNormalizer::normalizeInput($parameters['user'] ?? []);

        $parameters['patient'] ??= [];

        // Recepción puede dar de alta un paciente sin contraseña: nace sin cuenta
        // y la activa después con el link de "olvidé mi contraseña".
        // El teléfono es obligatorio: es la prueba con la que la reserva pública reconoce a un paciente
        // existente. Sin él, esa persona recibiría siempre el 409 neutral al reservar por la web.
        // El email es opcional: hay pacientes que no tienen. Sin email no reciben avisos de turnos.
        $userRules = array_merge(UserRequest::rules(passwordRequired: false), [
            'email' => IdentityRules::optional(IdentityRules::EMAIL),
            'phone' => IdentityRules::required(IdentityRules::PHONE),
        ]);

        RequestValidator::validate($parameters['user'], $userRules, UserRequest::messages());
        RequestValidator::validate($parameters['patient'], PatientDataRequest::rules(), PatientDataRequest::messages());

        $parameters = (object) $parameters;
        $parameters->user = (object) $parameters->user;
        $parameters->patient = (object) $parameters->patient;

        $userRequest = new UserRequest(
            $parameters->user->firstName,
            $parameters->user->lastName,
            $parameters->user->email ?? null,
            $parameters->user->phone ?? null,
            empty($parameters->user->password) ? null : $parameters->user->password,
            $parameters->user->dni,
            $parameters->user->isActive ?? true,
            $parameters->user->roleId ?? null,
        );

        $patientDataRequest = new PatientDataRequest(
            $parameters->patient->birthDate ?? null,
        );

        return new PatientRequest($userRequest, $patientDataRequest);
    }
}
