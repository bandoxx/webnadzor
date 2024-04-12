<?php

namespace App\Controller\Api;

use App\Repository\DeviceDataRepository;
use App\Repository\DeviceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DeviceController extends AbstractController
{

    #[Route(path: '/api/device/data/{id}', methods: 'GET')]
    public function read($id, DeviceDataRepository $deviceDataRepository)
    {
        return $this->json($deviceDataRepository->find($id), Response::HTTP_OK, [], ['groups' => 'device_read']);
    }

}