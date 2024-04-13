<?php

namespace App\Controller\Device\API;

use App\Repository\DeviceRepository;
use App\Service\Device\PurgeDeviceData;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/api/{clientId}/device/{deviceId}', name: 'api_device_delete', methods: 'POST')]
class DeviceDeleteController extends AbstractController
{

    public function __invoke($clientId, $deviceId, Request $request, DeviceRepository $deviceRepository, PurgeDeviceData $purgeDeviceData, UserPasswordHasherInterface $userPasswordChecker): JsonResponse
    {
        $user = $this->getUser();

        if ($user->getPermission() <= 3 || !$userPasswordChecker->isPasswordValid($user, $request->request->get('del_password', ''))) {
            return $this->json("Permission denied.", Response::HTTP_FORBIDDEN);
        }

        $device = $deviceRepository->find($deviceId);
        if (!$device) {
            return $this->json("Device not found.", Response::HTTP_NOT_FOUND);
        }

        $action = $request->request->get('del_action', '');

        dump($action);
        if (!in_array($action, ['delete_device', 'empty_data'], true)) {
            return $this->json("Bad request", Response::HTTP_BAD_REQUEST);
        }

        if ($action === 'delete_device') {
            $purgeDeviceData->removeAllDataRelatedToDevice($deviceId);
        } else {
            $purgeDeviceData->removeDeviceData($deviceId);
        }

        return $this->json(true, Response::HTTP_CREATED);

    }

}