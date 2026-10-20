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

    private function normalize(array $parameters): array
    {
        if (isset($parameters['slug']) && is_string($parameters['slug'])) {
            $parameters['slug'] = strtolower(trim($parameters['slug']));
        }

        if (array_key_exists('adminPassword', $parameters) && ($parameters['adminPassword'] === '' || $parameters['adminPassword'] === null)) {
            unset($parameters['adminPassword']);
        }

        $identity = array_filter([
            'dni'   => $parameters['adminDni'] ?? null,
            'email' => $parameters['adminEmail'] ?? null,
        ], static fn ($value) => $value !== null);

        $identity = IdentityNormalizer::normalizeInput($identity);

        if (array_key_exists('dni', $identity)) {
            $parameters['adminDni'] = $identity['dni'];
        }

        if (array_key_exists('email', $identity)) {
            $parameters['adminEmail'] = $identity['email'];
        }

        return $parameters;
    }
}
