<?php 
namespace App\Controllers\User;

use App\Dto\Request\User\UserRequest;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use App\Services\User\UserCreatorService;


class UserPostController extends ResourceController{
    private UserCreatorService  $userCreatorService;

    public function __construct(){
        $this->userCreatorService = new UserCreatorService();

    }

    public function create():ResponseInterface{
        $userRequest = $this->getRequest();
        $userResponse = $this->userCreatorService->create($userRequest);

        
        return $this->response->setJSON($userResponse);
    }
    
    private function getRequest():UserRequest
    {
        $parameters = $this->request->getJSON();
        
        return new UserRequest(
            $parameters->username,
            $parameters->email,
            $parameters->password,
            $parameters->dni,
            $parameters->isActive ?? true,
            $parameters->roleId
        );


    }

}