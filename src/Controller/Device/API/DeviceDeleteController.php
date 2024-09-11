<?php

namespace App\Controller\Device\API;

use App\Entity\User;
use App\Repository\DeviceRepository;
use App\Service\Device\PurgeDeviceData;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

#[Route(path: '/api/{clientId}/device/{deviceId}', name: 'api_device_delete', methods: 'POST')]
class DeviceDeleteController extends AbstractController
{

    public function __invoke(int $clientId, int $deviceId, Request $request, DeviceRepository $deviceRepository, PurgeDeviceData $purgeDeviceData, UserPasswordHasherInterface $userPasswordChecker): RedirectResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            throw new UserNotFoundException();
        }

        if ($user->isUser() || $user->isModerator()) {
            $this->addFlash('error', 'Nemate prava za brisanje uređaja.');

            return $this->redirectTo($clientId);
        }

        if (!$userPasswordChecker->isPasswordValid($user, $request->request->get('password_check', ''))) {
            $this->addFlash('error', 'Pogrešna lozinka.');

            return $this->redirectTo($clientId);
        }

        $device = $deviceRepository->find($deviceId);
        if (!$device) {
            return $this->redirectTo($clientId);
        }

        $action = $request->request->get('delete_action', '');

        if (!in_array($action, ['delete_device', 'empty_data'], true)) {
            $this->addFlash('error', 'Niste odabrali opciju za brisanje.');

            return $this->redirectTo($clientId);
        }

        if ($action === 'delete_device') {
            $purgeDeviceData->removeAllDataRelatedToDevice($deviceId);
        } else {
            $purgeDeviceData->removeDeviceData($deviceId);
        }

        return $this->redirectTo($clientId);
    }

    private function redirectTo(int $clientId): RedirectResponse
    {
        return $this->redirectToRoute('app_device_list', ['clientId' => $clientId]);
    }

}