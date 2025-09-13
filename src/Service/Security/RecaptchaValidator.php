<?php

namespace App\Service\Security;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class RecaptchaValidator
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly ?string $secret = null,
    ) {}

    /**
     * Validates Google reCAPTCHA v2 token.
     * If secret is not configured (empty), validation is skipped and returns true to avoid blocking in local/test.
     */
    public function validate(?string $token, ?string $remoteIp = null): bool
    {
        if (empty($this->secret)) {
            // No secret configured -> do not enforce validation
            return true;
        }

        if (empty($token)) {
            return false;
        }

        try {
            $response = $this->httpClient->request('POST', 'https://www.google.com/recaptcha/api/siteverify', [
                'body' => [
                    'secret' => $this->secret,
                    'response' => $token,
                    'remoteip' => $remoteIp,
                ],
            ]);

            if (200 !== $response->getStatusCode()) {
                return false;
            }

            $data = $response->toArray(false);
            return (bool)($data['success'] ?? false);
        } catch (\Throwable $e) {
            // On error, fail closed to prevent bypass
            return false;
        }
    }
}
