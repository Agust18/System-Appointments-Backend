<?php 

namespace App\Services\User;

use App\Converter\User\UserToUserResponseConverter;
use App\Dto\Request\User\UserFilterRequest;
use App\Dto\Response\User\UserResponse;
use App\Models\User\UserModel;

final class UsersSearcherService {

    private UserModel $userModel;
    private UserToUserResponseConverter $converter;

    public function __construct() {
        $this->userModel = new UserModel();
        $this->converter = new UserToUserResponseConverter();
    }

    /**
     * @return UserResponse[]
     */
    public function searchResponses(UserFilterRequest $request): array
    {
        $entities = $this->userModel->search($request);
         $total = $this->userModel->countByFilter($request);

        $responses = [];
        foreach ($entities as $entity) {
            $responses[] = $this->converter->convert($entity);
        }

        return [
            'data' => $responses,
            'total' => $total
        ];
    }
}