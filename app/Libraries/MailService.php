<?php

namespace App\Libraries;

class MailService
{
    public static function send(string $to, string $subject, string $view, array $data = []): bool
    {
        $email = \Config\Services::email();
        $config = config('Email');

        // 1. Force the correct configuration and line endings
        $email->initialize([
            'protocol'   => $config->protocol,
            'SMTPHost'   => $config->SMTPHost,
            'SMTPUser'   => $config->SMTPUser,
            'SMTPPass'   => $config->SMTPPass,
            'SMTPPort'   => $config->SMTPPort,
            'SMTPCrypto' => $config->SMTPCrypto,
            'mailType'   => 'html',
            'CRLF'       => "\r\n", // Native PHP line breaks!
            'newline'    => "\r\n"
        ]);
        
        // 2. Set the "From" address securely
        $email->setFrom($config->fromEmail, $config->fromName);

        // 3. Render the HTML view into a string
        $htmlMessage = view($view, $data);

        $email->setTo($to);
        $email->setSubject($subject);
        $email->setMessage($htmlMessage);

        // 4. Send and log errors if it fails
        if ($email->send()) {
            return true;
        } else {
            log_message('error', 'Email failed to send to ' . $to . '. Error: ' . $email->printDebugger(['headers']));
            return false;
        }
    }
}