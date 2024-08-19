<?php

namespace App\Controller\Archive;

use App\Repository\LoginLogArchiveRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/login-log/archive/{id}', name: 'api_login_log_archive_download')]
class DownloadLoginLogController extends AbstractController
{
    public function __invoke(int $id, string $archiveDirectory, LoginLogArchiveRepository $loginLogRepository): BinaryFileResponse|BadRequestHttpException
    {
        $archive = $loginLogRepository->find($id);

        if (!$archive) {
            throw new NotFoundHttpException();
        }

        $location = sprintf('%s/%s/%s/%s/%s.%s',
            $archiveDirectory,
            $archive->getClient()->getId(),
            'login_log',
            $archive->getArchiveDate()->format('Y/m/d'),
            $archive->getFilename(),
            'pdf'
        );

        if (!file_exists($location)) {
            throw new NotFoundHttpException();
        }

        return $this->file($location);
    }

}