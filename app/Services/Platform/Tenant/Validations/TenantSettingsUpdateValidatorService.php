<?php

namespace App\Services\Platform\Tenant\Validations;

use App\Dto\Request\Platform\TenantSettingsUpdateRequest;
use App\Exception\Validation\ValidationException;
use App\Tenancy\FrontendUrl;

final class TenantSettingsUpdateValidatorService
{
    private bool $allowHttp;

    /** @param bool|null $allowHttp null = `http` solo fuera de producción */
    public function __construct(?bool $allowHttp = null)
    {
        $this->allowHttp = $allowHttp ?? ENVIRONMENT !== 'production';
    }

    public function validate(TenantSettingsUpdateRequest $request): void
    {
        if ($request->isEmpty()) {
            throw new ValidationException(['body' => 'Enviá al menos un campo para modificar.']);
        }

        $errors = [];

        // name y receptionEmailEnabled no se pueden limpiar: un null explícito no es un "sin cambio".
        if ($request->has('name') && ($request->getName() === null || $request->getName() === '')) {
            $errors['name'] = 'El nombre no se puede dejar vacío.';
        }

        if ($request->has('receptionEmailEnabled') && $request->getReceptionEmailEnabled() === null) {
            $errors['receptionEmailEnabled'] = 'receptionEmailEnabled debe ser true o false.';
        }

        if ($request->has('frontendUrl') && $request->getFrontendUrl() !== null
            && FrontendUrl::normalize($request->getFrontendUrl(), $this->allowHttp) === null) {
            $errors['frontendUrl'] = $this->allowHttp
                ? 'La URL del frontend debe ser http(s)://dominio[:puerto], sin ruta, parámetros ni usuario.'
                : 'La URL del frontend debe ser https://dominio[:puerto], sin ruta, parámetros ni usuario.';
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }
}
