<?php
namespace App\Services\Role;

use App\Converter\Role\RoleToRoleResponseConverter;
use App\Dto\Request\Role\RoleFilterRequest;
use App\Models\Role\RoleModel;

final class RolesSearcherService
{
    private RoleModel $roleModel;
    private RoleToRoleResponseConverter $converter;

    public function __construct()
    {
        $this->roleModel = new RoleModel();
        $this->converter = new RoleToRoleResponseConverter();
    }

    public function search(RoleFilterRequest $request): array
    {
        $entities = $this->roleModel->search($request);
        $total = $this->roleModel->count($request);
        $responses = [];
        foreach ($entities as $entity) {
            $responses[] = $this->converter->convert($entity);
        }
        return [
            'data' => $responses,
            'total' => $total,
        ];
    }
}