<?php

namespace App\Controller\Archive;

use App\Entity\DeviceDataArchive;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/device/archive/{id}/{type}', name: 'api_device_data_archive_download')]
class DownloadDeviceArchiveController extends AbstractController
{

    public function __invoke(
        DeviceDataArchive $archive,
        string $type,
        string $archiveDirectory,
    ): BinaryFileResponse|BadRequestHttpException
    {
        if (!in_array($type, ['xlsx', 'pdf', 'enc'])) {
            return new BadRequestHttpException();
        }

        $location = sprintf('%s/%s/%s/%s/%s.%s',
            $archiveDirectory,
            $archive->getDevice()->getClient()->getId(),
            $archive->getPeriod(),
            $archive->getArchiveDate()->format('Y/m/d'),
            $archive->getFilename(),
            $type
        );

        if (!file_exists($location)) {
            throw new NotFoundHttpException();
        }

        return $this->file($location);
    }
}