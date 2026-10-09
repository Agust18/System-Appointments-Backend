<?php

namespace App\Controllers\Platform;

use App\Dto\Request\Platform\TenantRequest;
use App\Services\Platform\Tenant\TenantCreatorService;
use App\Support\IdentityNormalizer;
use App\Validation\RequestValidator;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

final class TenantPostController extends ResourceController
{
    private TenantCreatorService $tenantCreatorService;

    public function __construct()
    {
        $this->tenantCreatorService = new TenantCreatorService();
    }

    public function create(): ResponseInterface
    {
        $response = $this->tenantCreatorService->create($this->getRequest());

        return $this->response->setStatusCode(201)->setJSON($response);
    }

    private function getRequest(): TenantRequest
    {
        $parameters = $this->normalize($this->request->getJSON(true) ?? []);

        RequestValidator::validate($parameters, TenantRequest::rules(), TenantRequest::messages());

        $parameters = (object) $parameters;

        return new TenantRequest(
            $parameters->slug,
            $parameters->name,
            $parameters->adminFirstName,
            $parameters->adminLastName,
            $parameters->adminEmail,
            (int) $parameters->adminDni,
            $parameters->adminPassword ?? null,
            $parameters->frontendUrl
        );
    }

    /**
     * El slug es el subdominio: se guarda en minúsculas y sin espacios. El email y el DNI del ADMIN
     * se normalizan igual que en el alta de usuarios (IdentityNormalizer trabaja con las claves
     * `email` y `dni`, acá se llaman `adminEmail` y `adminDni`).
     */
    private function normalize(array $parameters): array
    {
        if (isset($parameters['slug']) && is_string($parameters['slug'])) {
            $parameters['slug'] = strtolower(trim($parameters['slug']));
        }

        // Vacío = sin contraseña (el ADMIN la elige con el link del email de bienvenida).
        if (array_key_exists('adminPassword', $parameters) && ($parameters['adminPassword'] === '' || $parameters['adminPassword'] === null)) {
            unset($parameters['adminPassword']);
        }

        if (isset($parameters['adminEmail']) && (is_string($parameters['adminEmail']) || is_int($parameters['adminEmail']))) {
            $parameters['adminEmail'] = IdentityNormalizer::email((string) $parameters['adminEmail']);
        }

        if (isset($parameters['adminDni']) && (is_string($parameters['adminDni']) || is_int($parameters['adminDni']))) {
            $parameters['adminDni'] = IdentityNormalizer::dni((string) $parameters['adminDni']);
        }

        return $parameters;
    }
}
