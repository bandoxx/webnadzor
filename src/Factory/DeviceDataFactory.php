<?php

namespace App\Factory;

use App\Entity\Device;
use App\Entity\DeviceData;
use App\Exception\XmlParserException;
use App\Service\XmlParser\ParserTemperatureChecker;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class DeviceDataFactory
{

    public function __construct(public ValidatorInterface $validator) {}

    /**
     * @throws \DateMalformedStringException
     * @throws XmlParserException
     */
    public function createFromXml(Device $device, string $filePath): DeviceData
    {
        $xmlData = file_get_contents($filePath);
        $xml = simplexml_load_string($xmlData);

        if ($xml) {
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

            $this->validate($deviceData);

            return $deviceData;
        }

        throw new XmlParserException(sprintf('XML Parser failed for %s, content of file: %s', $filePath, @file_get_contents($filePath)));
    }

    public function createFromArray(Device $device, array $data): DeviceData
    {
        $deviceData = new DeviceData();
        $deviceData
            ->setDevice($device)
            ->setServerDate(new \DateTime())
            ->setDeviceDate((new \DateTime())->setTimestamp($data['UTC']))
            ->setSupply($data['PI'])
            ->setGsmSignal($data['SS'])
            ->setVbat($data['BV'])
            ->setBattery($data['BP'])
            ->setT1($data['T1'])
            ->setRh1($data['RH1'])
            ->setMkt1($data['MKT1'])
            ->setTMin1($data['T1min'])
            ->setTMax1($data['T1max'])
            ->setTAvrg1($data['T1avg'])
            ->setD1(0)
            ->setT2($data['T2'])
            ->setRh2($data['RH2'])
            ->setMkt2($data['MKT2'])
            ->setTMin2($data['T2min'])
            ->setTMax2($data['T2max'])
            ->setTAvrg2($data['T2avg'])
            ->setD2(0)
        ;

        $this->validate($deviceData);

        return $deviceData;
    }

    private function validate(DeviceData $deviceData): void
    {
        $errors = $this->validator->validate($deviceData);

        if (count($errors) > 0) {
            throw new \RuntimeException('Validation failed');
        }
    }
}