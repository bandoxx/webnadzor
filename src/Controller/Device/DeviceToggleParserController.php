<?php

namespace App\Controller\Device;

use App\Repository\ClientRepository;
use App\Repository\DeviceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/admin/{clientId}/device/{deviceId}/toggle-parser', name: 'app_device_toggledeviceparser', methods: 'GET')]
class DeviceToggleParserController extends AbstractController
{
    public function __invoke(int $clientId, int $deviceId, ClientRepository $clientRepository, DeviceRepository $deviceRepository, EntityManagerInterface $entityManager): RedirectResponse
    {
        $device = $deviceRepository->find($deviceId);

        if (!$device) {
            throw new BadRequestException("Device doesn't exists.");
        }

        $device->setParserActive(!$device->isParserActive());

        $entityManager->flush();

        return $this->redirectToRoute('app_device_edit', ['clientId' => $clientId, 'deviceId' => $device->getId()]);
    }

}