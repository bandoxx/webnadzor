<?php

namespace App\Controller\Device;

use App\Entity\Client;
use App\Entity\Device;
use App\Entity\User;
use App\Model\TemperatureType;
use App\Repository\DeviceIconRepository;
use App\Service\DeviceUpdater;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/admin/{clientId}/device/{deviceId}/edit', name: 'app_device_edit', methods: 'GET|POST')]
class DeviceEditController extends AbstractController
{

    public function __invoke(
        #[MapEntity(id: 'clientId')]
        Client $client,
        #[MapEntity(id: 'deviceId')]
        Device $device,
        Request $request,
        DeviceIconRepository $deviceIconRepository,
        DeviceUpdater $deviceUpdater
    ): RedirectResponse|Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $icons = $deviceIconRepository->findAll();

        if ($device->isParserActive() === false) {
            $this->addFlash('error', 'Parser je isključen.');
        }

        if ($request->getMethod() === 'POST') {
            try {
                if ($user->isRoot() || $user->isAdministrator()) {
                    $errors = $deviceUpdater->update($device, $request->request->all());

                    return $this->redirectToRoute('app_device_edit', ['clientId' => $client->getId(), 'deviceId' => $device->getId()]);
                }

                return $this->redirectToRoute('client_overview', ['clientId' => $client->getId()]);
            } catch (\Throwable $e) {
                return $this->redirectToRoute('app_device_edit', ['clientId' => $client->getId(), 'deviceId' => $device->getId()]);
            }
        }

        return $this->render('v2/device/edit.html.twig', [
            'device' => $device,
            'icons' => $icons,
            'temperature_type' => TemperatureType::getTypes()
        ]);

    }

}