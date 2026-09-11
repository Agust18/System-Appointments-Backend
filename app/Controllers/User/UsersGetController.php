<?php

namespace App\Controllers\User;

use App\Dto\Request\PaginationRequest;
use App\Dto\Request\User\UserFilterRequest;
use App\Services\User\UsersSearcherService;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\ResponseInterface;

final class UsersGetController extends ResourceController
{
    private UsersSearcherService $usersSearcherService;

    public function __construct()
    {
        $this->usersSearcherService = new UsersSearcherService();
    }

    public function search(): ResponseInterface
    {
        $request = $this->getRequest();

        $responses = $this->usersSearcherService->searchResponses($request);

        return $this->response->setJSON($responses);
    }

    private function getRequest(): UserFilterRequest
    {
        $page = $this->request->getGet('page');
        $size = $this->request->getGet('size');
        $email = $this->request->getGet('email');
        $search = $this->request->getGet('search');
        $roleId = $this->request->getGet('roleId');
        $isActive = $this->request->getGet('isActive');

        $parsedIsActive = ($isActive !== null && $isActive !== '') 
        ? filter_var($isActive, FILTER_VALIDATE_BOOLEAN) 
        : null;

        return new UserFilterRequest(
            new PaginationRequest(
                $page ? (int) $page : 1,
                $size ? (int) $size : 10,
            ),
            $email ?? null,
            $search ?? null,
            ($roleId !== null && $roleId !== '') ? (int) $roleId : null,
            $parsedIsActive
            // $roleId ?? null,
            // $isActive ?? null

        );
    }
}