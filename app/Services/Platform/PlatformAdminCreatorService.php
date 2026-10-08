<?php

namespace App\Services\Platform;

use App\Converter\Platform\PlatformAdminToPlatformAdminResponseConverter;
use App\Dto\Request\Platform\PlatformAdminRequest;
use App\Dto\Response\Platform\PlatformAdminResponse;
use App\Entity\Platform\PlatformAdmin;
use App\Exception\Platform\PlatformAdminEmailAlreadyExistsException;
use App\Models\Platform\PlatformAdminModel;

final class PlatformAdminCreatorService
{
    private PlatformAdminModel $adminModel;
    private PlatformAdminToPlatformAdminResponseConverter $converter;

    public function __construct()
    {
        $this->adminModel = new PlatformAdminModel();
        $this->converter = new PlatformAdminToPlatformAdminResponseConverter();
    }

    public function create(PlatformAdminRequest $request): PlatformAdminResponse
    {
        if ($this->adminModel->findByEmail($request->getEmail()) !== null) {
            throw new PlatformAdminEmailAlreadyExistsException();
        }

        $admin = PlatformAdmin::create(
            $request->getEmail(),
            $request->getFirstName(),
            $request->getLastName(),
            $request->getPassword()
        );

        return $this->converter->convert($this->adminModel->insert($admin));
    }
}
