<?php

namespace App\Command\DatabaseMigration\Import;

use App\Dbal\MultipleInsertExecutor;
use App\Entity\Client;
use App\Entity\Device;
use App\Entity\DeviceAlarm;
use App\Entity\DeviceData;
use App\Entity\DeviceDataArchive;
use App\Entity\DeviceIcon;
use App\Service\Alarm\AlarmHandlerInterface;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;

class DeviceDataImport
{
    private MultipleInsertExecutor $deviceDataExecutor;
    private MultipleInsertExecutor $deviceDataArchiveExecutor;

    public function __construct(
        private EntityManagerInterface $entityManager,
        Connection $connection
    ) {
        $this->deviceDataExecutor = new MultipleInsertExecutor($connection, 'device_data');
        $this->deviceDataArchiveExecutor = new MultipleInsertExecutor($connection, 'device_data_archive');
    }

    public function import(\PDO $pdo, Client $client): void
    {
        $devices = $pdo->query('SELECT * FROM `config_ldevices`')->fetchAll(\PDO::FETCH_OBJ);

        $this->importDeviceIcons($pdo, $client);

        foreach ($devices as $deviceData) {
            echo sprintf("Importing %s \r\n", $deviceData->device_name);

            $client = $this->entityManager->getRepository(Client::class)->find($client->getId());
            $alarmData = $pdo->query(sprintf("SELECT * FROM `config_idevices` WHERE id = %s", $deviceData->id))->fetch(\PDO::FETCH_OBJ);

            if (!$alarmData) {
                $alarmData = [];
            }

            $device = $this->importDevice($deviceData, $client, $alarmData);

            $this->importDeviceData($pdo, $device->getId());
            $this->importArchive($pdo, $device->getId());
            $this->importAlarms($pdo, $device->getId());
        }
    }

    private function importDevice($deviceData, $client, $alarmData)
    {
        if ($device = $this->entityManager->getRepository(Device::class)->findOneBy(['client' => $client, 'oldId' => $deviceData->id])) {
            return $device;
        }

        $device = new Device();
        $device->setName($deviceData->device_name)
            ->setLatitude($deviceData->lat)
            ->setLongitude($deviceData->lng)
            ->setParserActive((bool) $deviceData->parser)
            ->setXmlName($deviceData->xml_name)
            ->setClient($client)
            ->setOldId($deviceData->id)
        ;

        $a1l = null;
        $a2l = null;
        $a1h = null;
        $a2h = null;
        $smtp1 = null;
        $smtp2 = null;
        $smtp3 = null;

        if ($alarmData) {
            $a1l = $alarmData->A1L ? ($alarmData->A1L - 1) / 100 : null;
            $a2l = $alarmData->A2L ? ($alarmData->A2L - 1) / 100 : null;
            $a1h = $alarmData->A1H ? ($alarmData->A1H - 1) / 100 : null;
            $a2h = $alarmData->A2H ? ($alarmData->A2H - 1) / 100 : null;
            $smtp1 = $alarmData->SmtpT1 ?: null;
            $smtp2 = $alarmData->SmtpT2 ?: null;
            $smtp3 = $alarmData->SmtpT3 ?: null;
        }

        $device->setEntry1([
            't_location' => $deviceData->t1_location ?? $deviceData->device_name ?? null,
            't_name' => $deviceData->t1_name,
            't_unit' => $deviceData->t1_unit,
            't_image' => $deviceData->t1_image,
            't_show_chart' => $deviceData->t1_show_chart,
            't_min' => $deviceData->t1_min ?? $a1l,
            't_max' => $deviceData->t1_max ?? $a1h,
            'rh_name' => $deviceData->rh1_name,
            'rh_unit' => $deviceData->rh1_unit,
            'rh_image' => $deviceData->rh1_image,
            'rh_show_chart' => $deviceData->rh1_show_chart,
            'rh_min' => $deviceData->rh1_min ?? null,
            'rh_max' => $deviceData->rh1_max ?? null,
            'd_name' => $deviceData->d1_name,
            'd_off_name' => $deviceData->d1_off_name,
            'd_on_name' => $deviceData->d1_on_name,
            'd_off_image' => $deviceData->d1_off_image,
            'd_on_image' => $deviceData->d1_on_image,
            't_use' => $deviceData->t1_use,
            'rh_use' => $deviceData->rh1_use,
            'd_use' => $deviceData->d1_use
        ]);

        $device->setEntry2([
            't_location' => $deviceData->t2_location ?? $deviceData->device_name ?? null,
            't_name' => $deviceData->t2_name,
            't_unit' => $deviceData->t2_unit,
            't_image' => $deviceData->t2_image,
            't_show_chart' => $deviceData->t2_show_chart,
            't_min' => $deviceData->t2_min ?? $a2l,
            't_max' => $deviceData->t2_max ?? $a2h,
            'rh_name' => $deviceData->rh2_name,
            'rh_unit' => $deviceData->rh2_unit,
            'rh_image' => $deviceData->rh2_image,
            'rh_show_chart' => $deviceData->rh2_show_chart,
            'rh_min' => $deviceData->rh2_min ?? null,
            'rh_max' => $deviceData->rh2_max ?? null,
            'd_name' => $deviceData->d2_name,
            'd_off_name' => $deviceData->d2_off_name,
            'd_on_name' => $deviceData->d2_on_name,
            'd_off_image' => $deviceData->d2_off_image,
            'd_on_image' => $deviceData->d2_on_image,
            't_use' => $deviceData->t2_use,
            'rh_use' => $deviceData->rh2_use,
            'd_use' => $deviceData->d2_use
        ]);

        $device->setAlarmEmail([
            'smtp1' => $smtp1,
            'smtp2' => $smtp2,
            'smtp3' => $smtp3
        ]);

        $this->entityManager->persist($device);
        $this->entityManager->flush();

        return $device;
    }

    private function importDeviceData(\PDO $pdo, $deviceId): void
    {
        $deviceDataRepository = $this->entityManager->getRepository(DeviceData::class);

        $newDevice = $this->entityManager->getRepository(Device::class)->find($deviceId);
        $query = sprintf("SELECT * FROM data_lunit_%d", $newDevice->getOldId());

        $firstRecord = $deviceDataRepository->getFirstRecord($newDevice->getId());
        $lastRecord = $deviceDataRepository->findLastRecordForDevice($newDevice);

        $limit = 20_000;

        $stmt = $pdo->query($query);

        $i = 0;
        while ($data = $stmt->fetch(\PDO::FETCH_OBJ)) {
            $deviceDate = new \DateTime($data->device_date);

            if ($firstRecord || $lastRecord) {
                if ($deviceDate < $lastRecord->getDeviceDate() && $deviceDate > $firstRecord->getDeviceDate()) {
                    continue;
                }
            }

            $i++;

            $this->deviceDataExecutor->enqueueData([
                'device_id' => $deviceId,
                'supply' => $data->supply,
                'gsm_signal' => $data->s,
                'vbat' => $data->vbat,
                'battery' => $data->batchrg,
                'device_date' => $data->device_date,
                'server_date' => $data->server_date,
                'd1' => $data->d1,
                't1' => $data->t1,
                'rh1' => $data->rh1,
                'mkt1' => $data->mkt1,
                't_max1' => $data->t1max,
                't_min1' => $data->t1min,
                't_avrg1' => $data->t1avrg,
                'note1' => $data->note1 ?? null,
                'd2' => $data->d2,
                't2' => $data->t2,
                'rh2' => $data->rh2,
                'mkt2' => $data->mkt2,
                't_max2' => $data->t2max,
                't_min2' => $data->t2min,
                't_avrg2' => $data->t2avrg,
                'note2' => $data->note2 ?? null,
            ]);

            if ($i % $limit === 0) {
                $this->deviceDataExecutor->execute();
                $i = 0;
                $this->entityManager->getConnection()->getConfiguration()->setMiddlewares([new \Doctrine\DBAL\Logging\Middleware(new \Psr\Log\NullLogger())]);
                $this->entityManager->clear();
                gc_collect_cycles();
            }
        }

        $this->deviceDataExecutor->execute();
        $this->entityManager->clear();
    }

    private function importArchive(\PDO $pdo, $deviceId)
    {
        $device = $this->entityManager->getRepository(Device::class)->find($deviceId);

        $queries = [
            DeviceDataArchive::PERIOD_DAY => sprintf("SELECT * FROM data_darchive WHERE ldevice_id = %d", $device->getOldId()),
            DeviceDataArchive::PERIOD_MONTH => sprintf("SELECT * FROM data_marchive WHERE ldevice_id = %d", $device->getOldId())
        ];

        $limit = 10_000;
        $i = 0;
        foreach ($queries as $period => $query) {
            $stmt = $pdo->query($query);

            while ($data = $stmt->fetch(\PDO::FETCH_OBJ)) {
                $i++;
                if (str_contains($data->filename, '_t1_')) {
                    $entries = [1];
                } elseif (str_contains($data->filename, '_t2_')) {
                    $entries = [2];
                } else {
                    $entries = Device::SENSOR_ENTRIES;
                }

                foreach ($entries as $entry) {
                    $this->deviceDataArchiveExecutor->enqueueData([
                        'device_id' => $deviceId,
                        'server_date' => $data->server_date,
                        'archive_date' => $data->archive_date,
                        'filename' => $data->filename,
                        'period' => $period,
                        'entry' => $entry
                    ]);
                }

                if ($i % $limit === 0) {
                    $i = 0;
                    $this->deviceDataArchiveExecutor->execute();
                    $this->entityManager->clear();
                }
            }
        }

        $this->deviceDataArchiveExecutor->execute();
        $this->entityManager->clear();
    }

    private function importAlarms(\PDO $pdo, $deviceId): void
    {
        $device = $this->entityManager->getRepository(Device::class)->find($deviceId);
        $query = sprintf("SELECT * FROM `alarms_lunit` WHERE ldevice_id = %d", $device->getOldId());

        $stmt = $pdo->query($query);

        while ($data = $stmt->fetch(\PDO::FETCH_OBJ)) {
            $alarm = new DeviceAlarm();

            $alarm->setDevice($device)
                ->setDeviceDate(new \DateTime($data->device_date))
                ->setServerDate(new \DateTime($data->server_date))
                ->setEndServerDate($data->end_server_date ? new \DateTime($data->end_server_date) : null)
                ->setEndDeviceDate($data->end_device_date ? new \DateTime($data->end_device_date) : null)
                ->setType($data->type === 'p' ? AlarmHandlerInterface::BATTERY_LEVEL : $data->type)
                ->setIsNotified(true)
            ;

            $this->entityManager->persist($alarm);
        }

        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    private function importDeviceIcons(\PDO $pdo, Client $client): void
    {
        $query = sprintf("SELECT * FROM `config_icons`");

        $stmt = $pdo->query($query);

        while ($data = $stmt->fetch(\PDO::FETCH_OBJ)) {
            if ($this->entityManager->getRepository(DeviceIcon::class)->findOneBy(['client' => $client, 'title' => $data->title, 'filename' => $data->filename])) {
                continue;
            }

            $icon = new DeviceIcon();

            $icon->setFilename($data->filename)
                ->setTitle($data->title)
                ->setClient($client)
            ;

            $this->entityManager->persist($icon);
            $this->entityManager->flush();
        }

    }
}