<?php

namespace App\Core;

/**
 * Verificacao server-side do Google reCAPTCHA v3 (invisivel, sem checkbox).
 * Se a secret key nao estiver configurada em settings, a verificacao e
 * pulada (comentarios seguem protegidos so pelo honeypot).
 */
class Recaptcha
{
    private const VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';
    private const MIN_SCORE = 0.5;

    public static function passes(string $secretKey, string $token): bool
    {
        if (trim($secretKey) === '') {
            return true;
        }

        if (trim($token) === '') {
            return false;
        }

        $payload = http_build_query([
            'secret' => $secretKey,
            'response' => $token,
        ]);

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => $payload,
                'timeout' => 5,
                'ignore_errors' => true,
            ],
        ]);

        $response = @file_get_contents(self::VERIFY_URL, false, $context);

        if ($response === false) {
            // Falha de rede não deve travar o formulário: cai para o
            // honeypot como única proteção nesta submissão.
            return true;
        }

        $data = json_decode($response, true);

        if (!is_array($data) || ($data['success'] ?? false) !== true) {
            return false;
        }

        return (float) ($data['score'] ?? 0) >= self::MIN_SCORE;
    }
}
