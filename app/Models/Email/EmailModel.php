<?php

namespace App\Models\Email;

use App\Dto\Response\Email\EmailResponse;
use App\Dto\Request\Email\EmailRequest;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

final readonly class EmailModel
{
    private const MAX_ATTACHMENT_SIZE = 3 * 1024 * 1024;

    public function send(EmailRequest $request): EmailResponse {
        if (!filter_var($request->getTo(), FILTER_VALIDATE_EMAIL)) {
            return EmailResponse::INVALID_EMAIL;
        }

        if (trim($request->getSubject()) === '') {
            return EmailResponse::EMPTY_SUBJECT;
        }

        if (trim($request->getBody()) === '') {
            return EmailResponse::EMPTY_BODY;
        }

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = $_ENV['EMAIL_HOST'] ?? '';
            $mail->SMTPAuth = true;
            $mail->Username = $_ENV['EMAIL_USERNAME'] ?? '';
            $mail->Password = $_ENV['EMAIL_PASSWORD'] ?? '';
            $mail->Port = (int) ($_ENV['EMAIL_PORT'] ?? 587);
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;

            if (
                empty($mail->Host) ||
                empty($mail->Username) ||
                empty($mail->Password)
            ) {
                return EmailResponse::SMTP_CONFIG_ERROR;
            }

            $mail->setFrom(
                $_ENV['EMAIL_FROM'] ?? $mail->Username,
                $_ENV['EMAIL_NAME'] ?? ''
            );

            $mail->addAddress($request->getTo());

            $replyTo = $request->getReplyTo();
            if ($replyTo !== null && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
                $mail->addReplyTo($replyTo);
            }

            if (!empty($request->getBcc())) {
                foreach($request->getBcc() as $bcc) {
                    $mail->addBCC($bcc);
                }
            }
            
            $mail->isHTML(true);
            $mail->Subject = $request->getSubject();
            $mail->Body = $request->getBody();
            $mail->AltBody = strip_tags($request->getBody());

            foreach ($request->getAttachments() as $path => $name) {
                if (!file_exists($path)) {
                    return EmailResponse::ATTACHMENT_NOT_FOUND;
                }

                if (filesize($path) > self::MAX_ATTACHMENT_SIZE) {
                    return EmailResponse::ATTACHMENT_TOO_LARGE;
                }

                $mail->addAttachment($path, $name);
            }

            $mail->CharSet = PHPMailer::CHARSET_UTF8;
            $mail->Encoding = PHPMailer::ENCODING_BASE64;

            $mail->send();

            return EmailResponse::OK;

        } catch (Exception $e) {
            log_message('error', 'SMTP Mail error: ' . $e->getMessage());

            if (str_contains($e->getMessage(), 'authenticate')) {
                return EmailResponse::SMTP_AUTH_ERROR;
            }

            return EmailResponse::SMTP_SEND_ERROR;
        } catch (\Throwable $e) {
            log_message('error', 'Unexpected mail error: ' . $e->getMessage());
            return EmailResponse::UNKNOWN_ERROR;
        }
    }
}