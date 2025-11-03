<?php

namespace App\Command\Archiver;

use App\Entity\Device;
use App\Entity\DeviceDataArchive;
use App\Factory\DeviceDataArchiveFactory;
use App\Repository\DeviceDataArchiveRepository;
use App\Repository\DeviceDataRepository;
use App\Repository\DeviceRepository;
use App\Service\Archiver\ArchiverInterface;
use App\Service\Archiver\DeviceData\DeviceDataPDFArchiver;
use App\Service\Archiver\DeviceData\DeviceDataXLSXArchiver;
use App\Service\Chart\ChartImageGenerator;
use App\Service\RawData\Factory\DeviceDataRawDataFactory;
use App\Service\RawData\RawDataHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:device-data-archiver:monthly',
    description: 'Command for making XLSX and PDF archives on monthly level.',
)]
class DeviceDataMonthlyArchiver extends Command
{
    public function __construct(
        private DeviceDataXLSXArchiver       $XLSXArchiver,
        private DeviceDataPDFArchiver        $PDFArchiver,
        private DeviceRepository             $deviceRepository,
        private DeviceDataRepository         $deviceDataRepository,
        private DeviceDataArchiveFactory     $deviceDataArchiveFactory,
        private DeviceDataArchiveRepository  $deviceDataArchiveRepository,
        private RawDataHandler               $rawDataHandler,
        private DeviceDataRawDataFactory     $deviceDataRawDataFactory,
        private EntityManagerInterface       $entityManager,
        private ChartImageGenerator          $chartImageGenerator
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('date', null, InputOption::VALUE_OPTIONAL, 'Any date within the target month', null)
            ->addOption('deviceId', null, InputOption::VALUE_OPTIONAL, 'Device id', null)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln(sprintf("%s - %s started", (new \DateTime())->format('Y-m-d H:i:s'), $this->getName()));

        if ($deviceId = $input->getOption('deviceId')) {
            $devices[] = $this->deviceRepository->find($deviceId);
        } else {
            $devices = $this->deviceRepository->findDevicesWithIdentifiers();
        }

        $date = $this->getTargetDate($input->getOption('date'));

        // For data fetching, use last month's date if no date was explicitly provided
        $dataFetchDate = $input->getOption('date') === null
            ? (clone $date)->modify('-1 month')
            : $date;

        // Batch size for flushing to database
        $batchSize = 20;
        $archiveCount = 0;

        // Pre-check which archives already exist to avoid unnecessary processing
        $existingMonthlyArchives = $this->preCheckExistingArchives($devices, $date);

        foreach ($devices as $device) {
            $data = $this->deviceDataRepository->findByDeviceAndForMonth($device, $dataFetchDate);

            foreach([1, 2] as $entry) {
                $fromDate = (clone $dataFetchDate)->modify('first day of this month')->setTime(0, 0, 0);
                $toDate = (clone $dataFetchDate)->modify('last day of this month')->setTime(23, 59, 59);

                // Check if archive already exists using the pre-fetched data
                $archiveKey = $this->getArchiveKey($device->getId(), $entry, $date, DeviceDataArchive::PERIOD_MONTH);
                if (isset($existingMonthlyArchives[$archiveKey])) {
                    continue;
                }

                $this->chartImageGenerator->generateTemperatureAndHumidityChartImage($device, $entry, $fromDate, $toDate);
                $this->generateMonthlyReport($device, $data, $entry, $date, false);

                $archiveCount++;
                // Flush every $batchSize archives
                if ($archiveCount % $batchSize === 0) {
                    $this->entityManager->flush();
                }
            }

            // Detach only DeviceData entities to free memory
            foreach ($data as $row) {
                $this->entityManager->detach($row);
            }

            unset($data);
            gc_collect_cycles();
        }

        // Final flush for any remaining archives
        if ($archiveCount % $batchSize !== 0) {
            $this->entityManager->flush();
        }

        $output->writeln(sprintf("%s - %s finished successfully", (new \DateTime())->format('Y-m-d H:i:s'), $this->getName()));
        return Command::SUCCESS;
    }

    private function generateMonthlyReport(Device $device, $data, $entry, $date, bool $flushImmediately = true): void
    {
        $fileName = $this->generateFilename(sprintf('d%s_%s', $device->getId(), $device->getDeviceIdentifier()), $entry, $date->format(ArchiverInterface::MONTHLY_FILENAME_FORMAT));

        $this->XLSXArchiver->saveMonthly($device,  $data, $entry, $date, $fileName);
        $archive = $this->PDFArchiver->saveMonthly($device, $data, $entry, $date, $fileName);

        $this->rawDataHandler->encrypt($this->deviceDataRawDataFactory->create($data, $entry, $date), $archive->getFullPathWithoutExtension());

        $archive = $this->deviceDataArchiveFactory->create($device, $date, $entry, $fileName, DeviceDataArchive::PERIOD_MONTH);

        $this->entityManager->persist($archive);

        if ($flushImmediately) {
            $this->entityManager->flush();
        }
    }

    private function generateFilename(string $identifier, $entry, $date): string
    {
        $text = sprintf('%s_t%s_%s', $identifier, $entry, $date);
        $text = preg_replace('/[^a-zA-Z0-9]+/u', '_', $text);
        $text = trim($text, '-');
        return mb_strtolower($text, 'UTF-8');
    }

    private function getTargetDate(?string $date = null): \DateTime
    {
        if ($date === null) {
            $target = new \DateTime();
            $target->modify('first day of this month');
        } else {
            try {
                $target = new \DateTime($date);
            } catch (\Exception $e) {
                $target = new \DateTime();
                $target->modify('first day of this month');
            }
        }

        $target->setTime(0, 0, 0);
        return $target;
    }

    private function preCheckExistingArchives(array $devices, \DateTime $date): array
    {
        $deviceIds = array_map(function (Device $device) {
            return $device->getId();
        }, $devices);

        $qb = $this->deviceDataArchiveRepository->createQueryBuilder('dda')
            ->where('dda.device IN (:device_ids)')
            ->andWhere('dda.period = :period')
            ->andWhere('dda.archiveDate = :archive_date')
            ->setParameter('device_ids', $deviceIds)
            ->setParameter('period', DeviceDataArchive::PERIOD_MONTH)
            ->setParameter('archive_date', $date);

        $existingArchives = $qb->getQuery()->getResult();

        $archiveMap = [];
        foreach ($existingArchives as $archive) {
            $key = $this->getArchiveKey(
                $archive->getDevice()->getId(),
                $archive->getEntry(),
                $archive->getArchiveDate(),
                $archive->getPeriod()
            );
            $archiveMap[$key] = true;
        }

        return $archiveMap;
    }

    private function getArchiveKey(int $deviceId, int $entry, \DateTime $date, string $period): string
    {
        return sprintf('%d_%d_%s_%s', $deviceId, $entry, $date->format('Y-m-d'), $period);
    }
}
