<?php

namespace App\Controller\Device\API;

use App\Factory\DeviceFactory;
use App\Repository\ClientRepository;
use App\Repository\DeviceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/api/{clientId}/device', name: 'api_device_create', methods: 'POST')]
class DeviceCreateController extends AbstractController
{

    public function __invoke(int $clientId, Request $request, ClientRepository $clientRepository, DeviceRepository $deviceRepository, DeviceFactory $deviceFactory, EntityManagerInterface $entityManager): JsonResponse
    {
        $xmlName = $request->request->get('xmlName');
        $deviceName = $request->request->get('deviceName');

        if ($deviceRepository->doesMoreThenOneXmlNameExists($request->request->get('xmlName', ''))) {
            throw new BadRequestException();
        }

        $client = $clientRepository->find($clientId);

        $device = $deviceFactory->create($client, $deviceName, $xmlName);
        $entityManager->persist($device);
        $entityManager->flush();

        return $this->json(true, Response::HTTP_CREATED);

    }

}