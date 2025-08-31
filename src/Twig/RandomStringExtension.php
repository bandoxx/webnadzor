<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class RandomStringExtension extends AbstractExtension
{
    private const ALPHABET = 'abcdefghijklmnopqrstuvwxyz0123456789';

    public function getFunctions(): array
    {
        return [
            new TwigFunction('random_string', [$this, 'randomString']),
        ];
    }

    /**
     * Generate a random alphanumeric string (lowercase letters and digits only).
     *
     * @param int $length Desired length, defaults to 5
     */
    public function randomString(int $length = 5): string
    {
        if ($length <= 0) {
            return '';
        }

        $alphabet = self::ALPHABET;
        $alphabetLength = strlen($alphabet);
        $result = '';

        for ($i = 0; $i < $length; $i++) {
            // cryptographically secure random index
            $index = random_int(0, $alphabetLength - 1);
            $result .= $alphabet[$index];
        }

        return $result;
    }
}
