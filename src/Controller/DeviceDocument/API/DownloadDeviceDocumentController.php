<?php

namespace App\Controller\DeviceDocument\API;

use App\Entity\DeviceDocument;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/device-document/{id}/download', name: 'api_device_document_download')]
class DownloadDeviceDocumentController extends AbstractController
{

    public function __invoke(DeviceDocument $deviceDocument, string $deviceDocumentDirectory): BinaryFileResponse|BadRequestHttpException
    {
        $location = sprintf('%s/%s',
            $deviceDocumentDirectory,
            $deviceDocument->getFile(),
        );

        if (!file_exists($location)) {
            throw new NotFoundHttpException();
        }

        return $this->file($location);
    }

}