<?php

namespace App\Services\Platform;

use App\Converter\Platform\PlatformAdminToPlatformAdminResponseConverter;
use App\Dto\Request\Platform\PlatformLoginRequest;
use App\Dto\Response\Platform\PlatformLoginResponse;
use App\Entity\Platform\PlatformAdminToken;
use App\Exception\Platform\PlatformAdminInactiveException;
use App\Exception\Platform\PlatformInvalidCredentialsException;
use App\Models\Platform\PlatformAdminModel;

final class PlatformLoginService
{
    /** Hash de una contraseña que nadie conoce: se verifica contra él cuando el email no existe. */
    private const DUMMY_HASH = '$2y$12$m.8T503FeLMluGIq162hveRhQV6cjaRgBB1FoIFVx2kucd6pUp6nu';

    private PlatformAdminModel $adminModel;
    private PlatformAdminToPlatformAdminResponseConverter $converter;

    public function __construct()
    {
        $this->adminModel = new PlatformAdminModel();
        $this->converter = new PlatformAdminToPlatformAdminResponseConverter();
    }

    public function login(PlatformLoginRequest $request): PlatformLoginResponse
    {
        $admin = $this->adminModel->findByEmail($request->getEmail());

        // Se verifica una contraseña aunque el email no exista, para que el tiempo de respuesta
        // no delate si el email existe.
        if ($admin === null) {
            password_verify($request->getPassword(), self::DUMMY_HASH);
            throw new PlatformInvalidCredentialsException();
        }

        if (!$admin->verifyPassword($request->getPassword())) {
            throw new PlatformInvalidCredentialsException();
        }

        // El 403 va después de la contraseña: solo quien la conoce se entera de que está desactivado.
        if (!$admin->isActive()) {
            throw new PlatformAdminInactiveException();
        }

        $this->adminModel->deleteExpiredTokens($admin->getId());

        $token = PlatformAdminToken::issueFor($admin->getId());
        $this->adminModel->saveToken($token);

        return new PlatformLoginResponse($token->getToken(), $this->converter->convert($admin));
    }
}
