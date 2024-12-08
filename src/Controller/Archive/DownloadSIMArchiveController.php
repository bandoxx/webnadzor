<?php

namespace App\Controller\Archive;

use App\Repository\ClientRepository;
use App\Repository\DeviceRepository;
use App\Service\SIM\SIMXSLXArchiver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/sim/archive/{clientId}/xslx', name: 'api_sim_archive_download')]
class DownloadSIMArchiveController extends AbstractController
{

    public function __invoke(
        int $clientId,
        Request $request,
        DeviceRepository $deviceRepository,
        ClientRepository $clientRepository,
        SIMXSLXArchiver $SIMXSLXArchiver
    ): StreamedResponse|BadRequestHttpException
    {
        $filled = $request->query->getBoolean('filled', false);

        $response = new StreamedResponse(function () use ($SIMXSLXArchiver, $deviceRepository, $clientRepository, $clientId, $filled) {
            $SIMXSLXArchiver->generate($clientRepository->find($clientId), $deviceRepository->findDevicesByClient($clientId, $filled));
        });

        $response->headers->set('Content-Type', 'application/vnd.ms-excel');
        $response->headers->set('Content-Disposition', sprintf('attachment;filename="%s-%s.xlsx"', 'SIM', (new \DateTime())->format('d-m-Y')));

        return $response;
    }
}