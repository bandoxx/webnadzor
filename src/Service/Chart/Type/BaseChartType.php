<?php

namespace App\Service\Chart\Type;

class BaseChartType
{

    protected static function convertDateTimeToChartStamp(\DateTimeInterface $dateTime): float
    {
        return floor($dateTime->getTimestamp() * 1000);
    }

}