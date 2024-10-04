<?php

namespace App\Controller\Device;

use App\Entity\Client;
use App\Entity\Device;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/admin/{clientId}/device/{deviceId}/toggle-parser', name: 'app_device_toggledeviceparser', methods: 'GET')]
class DeviceToggleParserController extends AbstractController
{
    public function __invoke(
        #[MapEntity(id: 'clientId')]
        Client $client,
        #[MapEntity(id: 'deviceId')]
        Device $device,
        EntityManagerInterface $entityManager
    ): RedirectResponse
    {
        $device->setParserActive(!$device->isParserActive());

        $entityManager->flush();

        return $this->redirectToRoute('app_device_edit', ['clientId' => $client->getId(), 'deviceId' => $device->getId()]);
    }

}