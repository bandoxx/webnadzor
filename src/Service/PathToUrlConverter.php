<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;

class PathToUrlConverter
{

    public function __construct(
        private RequestStack $requestStack
    ) {}

    public function convertToAbsoluteUrl(string $relativePath): string
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request) {
            throw new \RuntimeException('Request not set');
        }

        $baseUrl = $request->getSchemeAndHttpHost();

        $relativePath = ltrim($relativePath, '/');

        return sprintf("%s/%s", $baseUrl, $relativePath);
    }
}