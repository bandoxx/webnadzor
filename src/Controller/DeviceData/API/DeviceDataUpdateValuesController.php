<?php

namespace App\Controller\DeviceData\API;

use App\Entity\DeviceData;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(
    path: '/api/device-data/{id}/update-values',
    name: 'api_device_data_update_values',
    methods: ['POST']
)]
class DeviceDataUpdateValuesController extends AbstractController
{
    public function __invoke(
        #[MapEntity(id: 'id')]
        DeviceData $deviceData,
        Request $request,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        if ($user->getPermission() !== 4) {
            return $this->json([
                'success' => false,
                'message' => 'Nemate dozvolu za ovu akciju.',
            ], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->json([
                'success' => false,
                'message' => 'Neispravan format zahtjeva.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $updated = false;

        if (isset($data['t1'])) {
            $deviceData->setT1($data['t1'] === '' ? null : (string)$data['t1']);
            $updated = true;
        }

        if (isset($data['t2'])) {
            $deviceData->setT2($data['t2'] === '' ? null : (string)$data['t2']);
            $updated = true;
        }

        if (isset($data['rh1'])) {
            $deviceData->setRh1($data['rh1'] === '' ? null : (string)$data['rh1']);
            $updated = true;
        }

        if (isset($data['rh2'])) {
            $deviceData->setRh2($data['rh2'] === '' ? null : (string)$data['rh2']);
            $updated = true;
        }

        if (isset($data['tMin1'])) {
            $deviceData->setTMin1($data['tMin1'] === '' ? null : (string)$data['tMin1']);
            $updated = true;
        }

        if (isset($data['tMin2'])) {
            $deviceData->setTMin2($data['tMin2'] === '' ? null : (string)$data['tMin2']);
            $updated = true;
        }

        if (isset($data['tMax1'])) {
            $deviceData->setTMax1($data['tMax1'] === '' ? null : (string)$data['tMax1']);
            $updated = true;
        }

        if (isset($data['tMax2'])) {
            $deviceData->setTMax2($data['tMax2'] === '' ? null : (string)$data['tMax2']);
            $updated = true;
        }

        if (isset($data['mkt1'])) {
            $deviceData->setMkt1($data['mkt1'] === '' ? null : (string)$data['mkt1']);
            $updated = true;
        }

        if (isset($data['mkt2'])) {
            $deviceData->setMkt2($data['mkt2'] === '' ? null : (string)$data['mkt2']);
            $updated = true;
        }

        if (!$updated) {
            return $this->json([
                'success' => false,
                'message' => 'Nema podataka za ažuriranje.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Podaci uspješno ažurirani.',
            'data' => [
                'id' => $deviceData->getId(),
                't1' => $deviceData->getT1(),
                't2' => $deviceData->getT2(),
                'rh1' => $deviceData->getRh1(),
                'rh2' => $deviceData->getRh2(),
                'tMin1' => $deviceData->getTMin1(),
                'tMin2' => $deviceData->getTMin2(),
                'tMax1' => $deviceData->getTMax1(),
                'tMax2' => $deviceData->getTMax2(),
                'mkt1' => $deviceData->getMkt1(),
                'mkt2' => $deviceData->getMkt2(),
            ],
        ]);
    }
}
