<?php

namespace App\Service\XmlParser;

use App\Entity\Device;

class DeviceSettingsMaker
{

    public function __construct(
        private string $xmlDirectory
    ) {}

    public function saveXml(Device $device, array $data): void
    {
        $emails = $this->saveEmail($device, $data);
        $temperature = $this->saveTemperature($device, $data);

        $dataForUpdate = array_merge($emails, $temperature);

        $shouldSave = false;
        $xml = new \SimpleXMLElement('<Settings_RHTLV1.0/>', LIBXML_NOEMPTYTAG);

        foreach ($dataForUpdate as $field => $value) {
            $xml->$field = $value;
            $shouldSave = true;
        }

        $xmlLocation = sprintf("%s/%s-Settings.xml", $this->xmlDirectory, $device->getXmlName());
        $xmlData = $xml->asXML();

        if ($shouldSave) {
            file_put_contents($xmlLocation, $xmlData);
        }
    }

    private function saveTemperature(Device $device, array $data): array
    {
        $newList = [];

        foreach (range(1, 2) as $i) {
            $currentEntryData = $device->getEntryData($i);

            $currentMin = $currentEntryData['t_min'];
            $currentMax = $currentEntryData['t_max'];

            $newMin = $data["t{$i}_min"];
            $newMax = $data["t{$i}_max"];

            if ($currentMin != $newMin) {
                $newList["A{$i}L"] = $this->formatTemperature($newMin);
            }

            if ($currentMax != $newMax) {
                $newList["A{$i}H"] = $this->formatTemperature($newMax);
            }
        }

        return $newList;
    }

    private function saveEmail(Device $device, array $data): array
    {
        $currentEmails = $device->getAlarmEmail();
        $newList = [$data['smtp1'], $data['smtp2'], $data['smtp3']];

        if (count(array_diff($currentEmails, $newList)) === 0) {
            return [];
        }

        $xmlList = [];

        foreach (range(1, 3) as $i) {
            $email = $this->checkEmail($data["smtp$i"]);
            $xmlList["SmtpT$i"] = $email;
        }

        return $xmlList;
    }

    private function formatTemperature(?float $number): string
    {
        if ($number === null) {
            return "0000";
        }

        $number = $number * 100 + 1;
        if (strlen((string) $number) === 3) {
            return "0$number";
        }

        return (string) $number;
    }

    private function checkEmail(string $email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

}