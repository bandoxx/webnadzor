<?php

namespace App\Service\Device\Validator;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberUtil;

class DeviceDataValidator
{
    private PhoneNumberUtil $phoneUtil;
    private array $errors = [];

    public function __construct()
    {
        $this->phoneUtil = PhoneNumberUtil::getInstance();
    }

    public function validateLength(string $string, int $max = 1, int $min = 0): bool
    {
        $length = strlen(mb_convert_encoding($string, 'ISO-8859-1', 'UTF-8'));

        return $length >= $min && $length <= $max;
    }

    public function validateEmail(string $email): bool
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $this->errors[] = sprintf("%s email nije validan.", $email);
            return false;
        }
        
        return true;
    }

    public function validatePhoneNumber(string $phoneNumber): bool
    {
        try {
            $parsed = $this->phoneUtil->parse($phoneNumber, 'HR');
            if ($this->phoneUtil->isValidNumber($parsed) === false) {
                $this->errors[] = sprintf("Broj %s nije validan", $phoneNumber);
                return false;
            }
            return true;
        } catch (NumberParseException $e) {
            $this->errors[] = sprintf("Broj %s nije validan", $phoneNumber);
            return false;
        }
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function addError(string $error): void
    {
        $this->errors[] = $error;
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    public function clearErrors(): void
    {
        $this->errors = [];
    }
}