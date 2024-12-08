<?php
namespace App\Controller\Archive;

use App\Repository\DeviceRepository;
use App\Service\SIM\SIMXSLXArchiver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/sim/archive/admin/xslx', name: 'api_sim_archive_admin_download')]
class DownloadSIMAdminArchiveController extends AbstractController
{

    public function __invoke(
        Request $request,
        DeviceRepository $deviceRepository,
        SIMXSLXArchiver $SIMXSLXArchiver
    ): StreamedResponse|BadRequestHttpException
    {
        $filled = $request->query->getBoolean('filled', false);

        $response = new StreamedResponse(function () use ($SIMXSLXArchiver, $deviceRepository, $filled) {
            $SIMXSLXArchiver->generateAdmin($deviceRepository->findActiveDevices($filled));
        });

        $response->headers->set('Content-Type', 'application/vnd.ms-excel');
        $response->headers->set('Content-Disposition', sprintf('attachment;filename="%s-%s.xlsx"', 'SIM', (new \DateTime())->format('d-m-Y')));

        return $response;
    }
}