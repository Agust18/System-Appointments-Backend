<?php
namespace App\Services\User;

use App\Converter\User\UserToUserResponseConverter;
use App\Dto\Request\User\UserUpdaterRequest;
use App\Dto\Response\User\UserResponse;
use App\Models\User\UserModel;
use App\Services\User\UserFinderService;
use App\Services\User\Validations\UserUpdateValidatorService;
use App\Services\User\UserSyncService;

final class UserUpdaterService
{
    private UserModel $userModel;
    private UserToUserResponseConverter $converter;
    private UserFinderService $userFinderService;
    private UserUpdateValidatorService $validator;
    private UserSyncService $syncService;
    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->converter = new UserToUserResponseConverter();
        $this->userFinderService = new UserFinderService();
        $this->validator = new UserUpdateValidatorService();
        $this->syncService = new UserSyncService();
    }

    public function update(UserUpdaterRequest $request, int $id): UserResponse
    {
        $user = $this->userFinderService->find($id);
        
        $this->validator->validate($user, $request);
        
        $oldIsActive = $user->getIsActive();
        
        $oldRoleId = $user->getRoleId();
        
        $user->modify($request);
        
        $user = $this->userModel->update($user);
        
        $this->syncService->syncAll($user->getId(), $oldIsActive, $oldRoleId, $request);
        
        $response = $this->converter->convert($user);
        
        return $response;
        
    }
}
