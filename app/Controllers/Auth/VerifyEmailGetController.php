<?php

namespace App\Controllers\Auth;

use App\Dto\Request\Email\VerifyEmailRequest;
use App\Services\User\VerifyEmailService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

final class VerifyEmailGetController extends ResourceController
{
	private VerifyEmailService $service;

	public function __construct()
	{
		$this->service = new VerifyEmailService();
	}

	public function verify(): ResponseInterface
	{
		$request = $this->getRequest();
		$this->service->verify($request->getToken());

		return $this->response->setJSON(['message' => 'Email verificado correctamente.']);
	}

	private function getRequest(): VerifyEmailRequest
	{
		return new VerifyEmailRequest((string) $this->request->getGet('token'));
	}
}
