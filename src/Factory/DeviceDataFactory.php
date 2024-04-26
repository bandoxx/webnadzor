<?php

namespace App\Factory;

use App\Entity\Device;
use App\Entity\DeviceData;
use App\Service\XmlParser\ParserTemperatureChecker;

class DeviceDataFactory
{
    public function createFromXml(Device $device, string $filePath): ?DeviceData
    {
        $xmlData = @file_get_contents($filePath);

        if ($xml = @simplexml_load_string($xmlData)) {

            unlink($filePath);

            $created = @$xml->Created;
            $status = @$xml->Status;

            $deviceData = new DeviceData();
            $deviceData
                ->setDevice($device)
                ->setServerDate(new \DateTime())
                ->setDeviceDate(new \DateTime(sprintf("%s %s", $created->Date, $created->Time)))
                ->setSupply(@abs((int)$status->Supply))
                ->setGsmSignal(abs((int)@$status->S))
                ->setVbat(@abs((float)$status->Vbat))
                ->setBattery(@abs((int)$status->BatChrg))
                ->setT1(ParserTemperatureChecker::temperature((string)@$xml->T1))
                ->setRh1(ParserTemperatureChecker::relativeHumidity((string)@$xml->RH1))
                ->setMkt1(ParserTemperatureChecker::temperature((string)@$xml->MKT1))
                ->setTMin1(ParserTemperatureChecker::temperature((string)@$xml->T1Min))
                ->setTMax1(ParserTemperatureChecker::temperature((string)@$xml->T1Max))
                ->setTAvrg1(ParserTemperatureChecker::temperature((string)@$xml->T1avrg))
                ->setD1((int) @$xml->D1)
                ->setT2(ParserTemperatureChecker::temperature((string)@$xml->T2))
                ->setRh2(ParserTemperatureChecker::relativeHumidity((string)@$xml->RH2))
                ->setMkt2(ParserTemperatureChecker::temperature((string)@$xml->MKT2))
                ->setTMin2(ParserTemperatureChecker::temperature((string)@$xml->T2Min))
                ->setTMax2(ParserTemperatureChecker::temperature((string)@$xml->T2Max))
                ->setTAvrg2(ParserTemperatureChecker::temperature((string)@$xml->T2avrg))
                ->setD2((int) @$xml->D2)
            ;

            return $deviceData;
        }

        return null;
    }
}