<?php

namespace App\Controller\DeviceData;

use App\Entity\Client;
use App\Entity\User;
use App\Service\ClientStorage\Types\DeviceTypesDropdown;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/{clientId}/devices-shift', name: 'device_data_shift_updater', methods: ['GET'])]
class DeviceDataViewController extends AbstractController
{
    public function __construct(
        private readonly DeviceTypesDropdown $deviceTypesDropdown
    ) {
    }

    public function __invoke(
        #[MapEntity(id: 'clientId')]
        Client $client
    ): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($user->getPermission() !== 4) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('v2/device/device_data.html.twig', [
            'client' => $client,
            'deviceTypesDropdown' => $this->deviceTypesDropdown->getForClient($client),
        ]);
    }
}
