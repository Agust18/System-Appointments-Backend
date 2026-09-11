<?php

namespace App\Dto\Request\Email;

final readonly class EmailRequest
{
    public function __construct(
        private string $to,
        private string $subject,
        private string $body,
        private array $attachments = [],
        private array $bcc = [],
    ) {
    }

    public function getTo(): string
    {
        return $this->to;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getAttachments(): array
    {
        return $this->attachments;
    }

    public function getBcc(): array
    {
        return $this->bcc;
    }
}