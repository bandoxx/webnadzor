<?php

namespace App\Factory;

use App\Entity\SmsDeliveryReport;

class SmsDeliveryFactory
{

    public function create(string $messageId, string $sentTo, string $statusDescription, string $statusName, string $errorDescription, string $errorName, string $sentAt): SmsDeliveryReport
    {
        try {
            $sentAt = new \DateTime($sentAt);
        } catch (\Exception $e) {
            $sentAt = null;
        }

        return (new SmsDeliveryReport())
            ->setMessageId($messageId)
            ->setSentTo($sentTo)
            ->setStatusDescription($statusDescription)
            ->setStatusName($statusName)
            ->setErrorDescription($errorDescription)
            ->setErrorName($errorName)
            ->setSentAt($sentAt)
        ;
    }

}