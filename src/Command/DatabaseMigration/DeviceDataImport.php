<?php

namespace App\Command\DatabaseMigration;

use App\Entity\Client;
use App\Entity\Device;
use App\Entity\DeviceAlarm;
use App\Entity\DeviceData;
use App\Entity\DeviceDataArchive;
use App\Entity\DeviceIcon;
use App\Factory\DeviceDataEntryFactory;
use Doctrine\ORM\EntityManagerInterface;

class DeviceDataImport
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function import(\PDO $pdo, Client $client): void
    {
        $devices = $pdo->query('SELECT * FROM `config_ldevices`')->fetchAll(\PDO::FETCH_OBJ);

        $this->importDeviceIcons($pdo, $client);

        foreach ($devices as $deviceData) {
            echo sprintf("Importing %s \r\n", $deviceData->device_name);

            $client = $this->entityManager->getRepository(Client::class)->find($client->getId());
            $alarmData = $pdo->query(sprintf("SELECT * FROM `config_idevices` WHERE id = %s", $deviceData->id))->fetch(\PDO::FETCH_OBJ);

            $device = $this->importDevice($deviceData, $client, $alarmData);

            $this->importDeviceData($pdo, $device->getId());
            $this->importArchiveDaily($pdo, $device->getId());
            $this->importArchiveMonthly($pdo, $device->getId());
            $this->importAlarms($pdo, $device->getId());
        }
    }

    private function importDevice($deviceData, $client, $alarmData)
    {
        $device = new Device();
        $device->setName($deviceData->device_name)
            ->setLatitude($deviceData->lat)
            ->setLongitude($deviceData->lng)
            ->setParserActive((bool) $deviceData->parser)
            ->setXmlName($deviceData->xml_name)
            ->setClient($client)
            ->setOldId($deviceData->id)
        ;

        $device->setEntry1([
            't_location' => $deviceData->t1_location ?? $deviceData->device_name ?? null,
            't_name' => $deviceData->t1_name,
            't_unit' => $deviceData->t1_unit,
            't_image' => $deviceData->t1_image,
            't_show_chart' => $deviceData->t1_show_chart,
            't_min' => $deviceData->t1_min ?? $alarmData->A1L ? ($alarmData->A1L - 1) / 100 : null,
            't_max' => $deviceData->t1_max ?? $alarmData->A1H ? ($alarmData->A1H - 1) / 100 : null,
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
            't_min' => $deviceData->t2_min ?? $alarmData->A2L ? ($alarmData->A2L - 1) / 100 : null,
            't_max' => $deviceData->t2_max ?? $alarmData->A2H ? ($alarmData->A2H - 1) / 100 : null,
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

        $this->entityManager->persist($device);
        $this->entityManager->flush();

        return $device;
    }

    private function importDeviceData(\PDO $pdo, $deviceId): void
    {
        $newDevice = $this->entityManager->getRepository(Device::class)->find($deviceId);
        $query = sprintf("SELECT * FROM data_lunit_%d", $newDevice->getOldId());
        $limit = 10_000;

        $stmt = $pdo->query($query);

        $i = 0;
        while ($data = $stmt->fetch(\PDO::FETCH_OBJ)) {
            $i++;
            $newDeviceData = new DeviceData();

            $newDeviceData->setDevice($newDevice)
                ->setVbat($data->vbat)
                ->setSupply((bool) $data->supply)
                ->setGsmSignal($data->s)
                ->setBattery($data->batchrg)
                ->setDeviceDate(new \DateTime($data->device_date))
                ->setServerDate(new \DateTime($data->server_date))
                ->setD1($data->d1)
                ->setT1($data->t1)
                ->setRh1($data->rh1)
                ->setMkt1($data->mkt1)
                ->setTMax1($data->t1max)
                ->setTMin1($data->t1min)
                ->setTAvrg1($data->t1avrg)
                ->setNote1($data->note1 ?? null)
                ->setD2($data->d2)
                ->setT2($data->t2)
                ->setRh2($data->rh2)
                ->setMkt2($data->mkt2)
                ->setTMax2($data->t2max)
                ->setTMin2($data->t2min)
                ->setTAvrg2($data->t2avrg)
                ->setNote2($data->note2 ?? null)
            ;

            $this->entityManager->persist($newDeviceData);

            if ($i % $limit === 0) {
                $i = 0;
                $this->entityManager->flush();
                $this->entityManager->getConnection()->getConfiguration()->setMiddlewares([new \Doctrine\DBAL\Logging\Middleware(new \Psr\Log\NullLogger())]);
                $this->entityManager->clear();
                gc_collect_cycles();
                $newDevice = $this->entityManager->getRepository(Device::class)->find($deviceId);
            }
        }

        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    private function importArchiveDaily(\PDO $pdo, $deviceId): void
    {
        $device = $this->entityManager->getRepository(Device::class)->find($deviceId);
        $query = sprintf("SELECT * FROM data_darchive WHERE ldevice_id = %d", $device->getOldId());
        $limit = 10_000;

        $stmt = $pdo->query($query);

        $i = 0;
        while ($data = $stmt->fetch(\PDO::FETCH_OBJ)) {
            $i++;
            if (str_contains($data->filename, '_t1_')) {
                $entries = [1];
            } elseif (str_contains($data->filename, '_t2_')) {
                $entries = [2];
            } else {
                $entries = [1, 2];
            }

            foreach ($entries as $entry) {
                $deviceArchive = new DeviceDataArchive();
                $deviceArchive->setDevice($device)
                    ->setServerDate(new \DateTime($data->server_date))
                    ->setArchiveDate(new \DateTime($data->archive_date))
                    ->setFilename($data->filename)
                    ->setPeriod(DeviceDataArchive::PERIOD_DAY)
                    ->setEntry($entry)
                ;

                $this->entityManager->persist($deviceArchive);
            }

            if ($i % $limit === 0) {
                $i = 0;
                $this->entityManager->flush();
            }
        }

        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    private function importArchiveMonthly(\PDO $pdo, $deviceId): void
    {
        $device = $this->entityManager->getRepository(Device::class)->find($deviceId);
        $query = sprintf("SELECT * FROM data_marchive WHERE ldevice_id = %d", $device->getOldId());
        $limit = 10_000;

        $stmt = $pdo->query($query);

        $i = 0;
        while ($data = $stmt->fetch(\PDO::FETCH_OBJ)) {
            $i++;
            if (str_contains($data->filename, '_t1_')) {
                $entries = [1];
            } elseif (str_contains($data->filename, '_t2_')) {
                $entries = [2];
            } else {
                $entries = [1, 2];
            }

            foreach ($entries as $entry) {
                $deviceArchive = new DeviceDataArchive();
                $deviceArchive->setDevice($device)
                    ->setServerDate(new \DateTime($data->server_date))
                    ->setArchiveDate(new \DateTime($data->archive_date))
                    ->setFilename($data->filename)
                    ->setPeriod(DeviceDataArchive::PERIOD_MONTH)
                    ->setEntry($entry)
                ;

                $this->entityManager->persist($deviceArchive);
            }

            if ($i % $limit === 0) {
                $i = 0;
                $this->entityManager->flush();
            }
        }

        $this->entityManager->flush();
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
                ->setSensor($data->sensor)
                ->setType($data->type)
            ;

            $this->entityManager->persist($alarm);
        }

        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    private function importDeviceIcons(\PDO $pdo, Client $client)
    {
        $query = sprintf("SELECT * FROM `config_icons`");

        $stmt = $pdo->query($query);

        while ($data = $stmt->fetch(\PDO::FETCH_OBJ)) {
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