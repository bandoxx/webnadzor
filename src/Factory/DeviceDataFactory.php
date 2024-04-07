<?php

namespace App\Factory;

use App\Entity\DeviceData;
use App\Repository\DeviceRepository;
use App\Service\XmlParser\ParserTemperatureChecker;

class DeviceDataFactory
{
    public function __construct(private DeviceRepository $repository) {}

    public function createFromXml(string $filePath): ?DeviceData
    {
        $device = $this->repository->findOneBy(['xmlFile' => $filePath]);

        $xml_data = @file_get_contents($filePath);

        if ($xml = @simplexml_load_string($xml_data)) {

            //unlink($filePath); remove a file

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
                ->setT1(ParserTemperatureChecker::temperature((float)@$xml->T1))
                ->setRh1(ParserTemperatureChecker::relativeHumidity((float)@$xml->RH1))
                ->setMkt1(ParserTemperatureChecker::temperature((float)@$xml->MKT1))
                ->setTMin1(ParserTemperatureChecker::temperature((float)@$xml->T1Min))
                ->setTMax1(ParserTemperatureChecker::temperature((float)@$xml->T1Max))
                ->setTAvrg1(ParserTemperatureChecker::temperature((float)@$xml->T1avrg))
                ->setD1((int) @$xml->D1)
                ->setT2(ParserTemperatureChecker::temperature((float)@$xml->T2))
                ->setRh2(ParserTemperatureChecker::relativeHumidity((float)@$xml->RH2))
                ->setMkt2(ParserTemperatureChecker::temperature((float)@$xml->MKT2))
                ->setTMin2(ParserTemperatureChecker::temperature((float)@$xml->T2Min))
                ->setTMax2(ParserTemperatureChecker::temperature((float)@$xml->T2Max))
                ->setTAvrg2(ParserTemperatureChecker::temperature((float)@$xml->T2avrg))
                ->setD2((int) @$xml->D2)
            ;

            return $deviceData;
        }

        return null;
    }
}