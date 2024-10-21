<?php

namespace App\Service\Model\InfoBipClient;

class AccountBalance
{

    public function __construct(
        public float $balance,
        public string $currency
    ) {}

}