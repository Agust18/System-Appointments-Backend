<?php
namespace App\Services\User;

use App\Converter\User\UserToUserResponseConverter;
use App\Dto\Request\User\UserUpdaterRequest;
use App\Dto\Response\User\UserResponse;
use App\Models\User\UserModel;
use App\Services\User\UserFinderService;
use App\Services\User\Validations\UserUpdateValidatorService;

final class UserUpdaterService
{
    private UserModel $userModel;
    private UserToUserResponseConverter $converter;
    private UserFinderService $userFinderService;
    private UserUpdateValidatorService $validator;
    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->converter = new UserToUserResponseConverter();
        $this->userFinderService = new UserFinderService();
        $this->validator = new UserUpdateValidatorService();
    }

    public function update(UserUpdaterRequest $request, int $id): UserResponse
    {
        $user = $this->userFinderService->find($id);
        
        $this->validator->validate($user, $request);
        
        $user->modify($request);

        $user = $this->userModel->update($user);

        $response = $this->converter->convert($user);
        
        return $response;
        
    }
}
