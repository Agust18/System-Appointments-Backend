<?php

namespace App\Email;

use App\Dto\Request\Email\EmailRequest;
use App\Dto\Response\Email\EmailResponse;
use App\Exception\Email\EmailCannotBeSendedException;
use App\Models\Email\EmailModel;

final readonly class EmailSenderService
{
    private EmailModel $emailModel;

    public function __construct()
    {
        $this->emailModel = new EmailModel();
    }

    public function send(EmailRequest $request): EmailResponse
    {
        $response = $this->emailModel->send($request);

        if ($response->isError()) {
            throw new EmailCannotBeSendedException($response->message());
        }

        return $response;
    }
}