<?php

namespace App\Controller\Device\API;

use App\Factory\DeviceFactory;
use App\Repository\ClientRepository;
use App\Repository\DeviceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/api/{clientId}/device', name: 'api_device_create', methods: 'POST')]
class DeviceCreateController extends AbstractController
{

    public function __invoke(int $clientId, Request $request, ClientRepository $clientRepository, DeviceRepository $deviceRepository, DeviceFactory $deviceFactory, EntityManagerInterface $entityManager): RedirectResponse
    {
        $xmlName = $request->request->get('xmlName', '');

        if ($deviceRepository->doesMoreThenOneXmlNameExists($xmlName)) {
            $this->addFlash('error', sprintf("Xml naziv: `%s` već postoji!", $xmlName));
            return $this->redirectToRoute('app_device_list', ['clientId' => $clientId]);
        }

        $client = $clientRepository->find($clientId);

        $device = $deviceFactory->create($client, $xmlName);
        $entityManager->persist($device);
        $entityManager->flush();

        return $this->redirectToRoute('app_device_list', ['clientId' => $clientId]);

    }

}