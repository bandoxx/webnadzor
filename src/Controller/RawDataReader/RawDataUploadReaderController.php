<?php

declare(strict_types=1);

namespace App\Controller\RawDataReader;

use App\Service\Crypto\PNG\Decrypt;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/raw-data-reader', name: 'api_raw-data-reader', methods: 'POST')]
class RawDataUploadReaderController extends AbstractController
{
    public function __invoke(Decrypt $decrypt, Request $request): Response
    {
        /** @var UploadedFile $file */
        $file = $request->files->get('raw_data_file');
        $outputFile = sys_get_temp_dir() . '/image_'. uniqid() . '.png';
        $decrypt->decrypt($file->getPathname(), $outputFile);

        $response = new StreamedResponse(
            function () use ($outputFile) {
                $stream = fopen($outputFile, 'rb');
                fpassthru($stream);
                fclose($stream);
                unlink($outputFile);
            }
        );

        $response->headers->set('Content-Type', 'image/png');
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $response->setSharedMaxAge(60);

        return $response;
    }
}
