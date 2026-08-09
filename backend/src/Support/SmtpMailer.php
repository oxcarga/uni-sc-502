<?php

declare(strict_types=1);

namespace App\Support;

use RuntimeException;

/**
 * Cliente SMTP mínimo (suficiente para Mailhog / SMTP sin auth en local).
 */
class SmtpMailer
{
    public function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly string $from,
    ) {
    }

    public function send(string $to, string $subject, string $bodyText): void
    {
        $socket = @fsockopen($this->host, $this->port, $errno, $errstr, 5);
        if ($socket === false) {
            throw new RuntimeException("No se pudo conectar a SMTP {$this->host}:{$this->port} ({$errno} {$errstr})");
        }

        stream_set_timeout($socket, 5);

        try {
            $this->expect($socket, 220);
            $this->command($socket, 'EHLO pulso-solidario', 250);
            $this->command($socket, 'MAIL FROM:<' . $this->from . '>', 250);
            $this->command($socket, 'RCPT TO:<' . $to . '>', 250);
            $this->command($socket, 'DATA', 354);

            $headers = [
                'From: ' . $this->from,
                'To: ' . $to,
                'Subject: ' . $this->encodeSubject($subject),
                'MIME-Version: 1.0',
                'Content-Type: text/plain; charset=UTF-8',
                'Content-Transfer-Encoding: 8bit',
            ];

            $data = implode("\r\n", $headers) . "\r\n\r\n" . $this->dotStuff($bodyText) . "\r\n.";
            $this->command($socket, $data, 250);
            $this->command($socket, 'QUIT', 221);
        } finally {
            fclose($socket);
        }
    }

    private function encodeSubject(string $subject): string
    {
        return '=?UTF-8?B?' . base64_encode($subject) . '?=';
    }

    private function dotStuff(string $body): string
    {
        $normalized = str_replace(["\r\n", "\r"], "\n", $body);
        $normalized = str_replace("\n", "\r\n", $normalized);

        return preg_replace('/^\./m', '..', $normalized) ?? $normalized;
    }

    private function command($socket, string $command, int $expectedCode): void
    {
        fwrite($socket, $command . "\r\n");
        $this->expect($socket, $expectedCode);
    }

    private function expect($socket, int $expectedCode): void
    {
        $response = '';
        while (($line = fgets($socket, 515)) !== false) {
            $response .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }

        $code = (int) substr($response, 0, 3);
        if ($code !== $expectedCode) {
            throw new RuntimeException("SMTP esperaba {$expectedCode}, recibió: " . trim($response));
        }
    }
}
