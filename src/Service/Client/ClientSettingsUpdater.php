<?php

namespace App\Service\Client;

use App\Entity\Client;
use App\Entity\ClientFtp;
use App\Entity\ClientSetting;
use App\Repository\ClientFtpRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ClientSettingsUpdater
{

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ClientFtpRepository $clientFtpRepository
    ) {}

    public function update(Client $client, Request $request): void
    {
        $data = $request->request->all();

        $this->updateClientSettings($client->getClientSetting(), $data);

        $ftp = $this->clientFtpRepository->findOneBy(['client' => $client]);

        if (!$ftp) {
            throw new BadRequestHttpException();
        }

        $this->updateFTPSettings($ftp, $data);

        $this->entityManager->flush();
    }

    private function updateClientSettings(ClientSetting $clientSetting, array $data): void
    {
        $clientSetting
            ->setDeviceSignalAlarmActive(isset($data['is_device_signal_level_active']))
            ->setDeviceSignalAlarm($data['device_signal_level'])
            ->setDeviceOfflineAlarmActive(isset($data['is_device_offline_alarm_active']))
            ->setBatteryLevelAlarmActive(isset($data['is_battery_level_alarm_active']))
            ->setDeviceSensorErrorAlarmActive(isset($data['is_device_sensor_error_alarm_active']))
            ->setIsTemperatureAlarmActive(isset($data['is_temperature_alarm_active']))
            ->setIsHumidityAlarmActive(isset($data['is_humidity_alarm_active']))
            ->setBatteryLevelAlert($data['battery_level_alarm'])
        ;

        $emailList = [];

        for ($i = 1; $i <= 5; $i++) {
            $key = "email_list$i";

            if (!$data[$key]) {
                continue;
            }

            $emailList[] = $data[$key];
        }

        $emailList = array_values(array_unique($emailList));

        $clientSetting->setAlarmNotificationList($emailList);
    }

    private function updateFTPSettings(ClientFtp $ftp, array $data)
    {
        $ftp->setHost($data['host']);
        $ftp->setUsername($data['username']);
        $ftp->setPassword($data['password']);
    }
}