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

    #[Route(path: '/api/device/{id}')]
    public function newDevice($id, DeviceRepository $deviceRepository, DeviceDataRepository $deviceDataRepository)
    {
        $device = $deviceRepository->find($id);

        $data = array_map(static function ($data) {
            return [
                'date' => date_timestamp_get($data['deviceDate']),
                't' => $data['t']
            ];
        }, $deviceDataRepository->createQueryBuilder('dd')->select('dd.deviceDate', "JSON_EXTRACT(dd.entry1, $.t)")->where('dd.device = 732')->getQuery()->getResult());



        return $this->json($data);
    }

    //#[Route(path: '/api/device/{id}')]
    //public function newDevice($id, DeviceRepository $deviceRepository, DeviceDataRepository $deviceDataRepository)
    //{
    //    $device = $deviceRepository->find($id);
    //
    //    return $this->json($deviceDataRepository->getMaxRangeForDevice($device));
    //}

}