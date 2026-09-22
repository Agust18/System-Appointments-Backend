<?php

namespace App\Controllers\Auth;

use App\Dto\Request\Email\ResendVerificationEmailRequest;
use App\Services\User\ResendVerificationEmailService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

final class ResendVerificationPostController extends ResourceController
{
	private ResendVerificationEmailService $service;

	public function __construct()
	{
		$this->service = new ResendVerificationEmailService();
	}

	public function resend(): ResponseInterface
	{
		$request = $this->getRequest();
		$this->service->resend($request->getEmail());

		return $this->response->setJSON([
			'message' => 'Si el email corresponde a una cuenta pendiente, recibirás un nuevo enlace.',
		]);
	}

	private function getRequest(): ResendVerificationEmailRequest
	{
		$parameters = $this->request->getJSON();

		return new ResendVerificationEmailRequest((string) $parameters->email);
	}
}
