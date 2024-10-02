<?php

declare(strict_types=1);

namespace App\Controller\RawDataReader;

use App\Service\RawData\RawDataHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/raw-data-reader', name: 'api_raw-data-reader', methods: 'POST')]
class RawDataUploadReaderController extends AbstractController
{
    public function __invoke(Request $request, RawDataHandler $rawDataHandler): Response
    {
        /** @var UploadedFile $file */
        $file = $request->files->get('raw_data_file');

        $data = $rawDataHandler->decryptUploadedFile($file);

        $header = $data[0];
        unset($data[0]);

        return $this->render('v2/raw_data/table.html.twig', [
            'headers' => $header,
            'dataset' => $data
        ]);
    }
}
