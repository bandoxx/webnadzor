<?php

namespace App\Controller;

use App\Entity\User;
use App\Model\DeviceListModel;
use App\Model\DeviceOverviewModel;
use App\Repository\DeviceDataRepository;
use App\Repository\DeviceRepository;
use App\Service\DeviceLocationHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

class HomeController extends AbstractController
{

    #[Route(path: '/', name: 'app_index')]
    public function index(UserInterface $user)
    {
        if ($user->getUserIdentifier()) {
            return $this->redirectToRoute('app_home_overview');
        }

        return $this->redirectToRoute('app_login');
    }

    #[Route(path: '/overview', name: 'app_home_overview')]
    public function overview(DeviceLocationHandler $deviceLocationHandler)
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('overview/overview.html.twig', [
            'devices_table' => $deviceLocationHandler->getClientDeviceLocationData($user->getClient())
        ]);
    }

    #[Route(path: '/device-list', name: 'app_device_list')]
    public function deviceList(DeviceRepository $deviceRepository, DeviceDataRepository $deviceDataRepository)
    {
        /** @var User $user */
        $user = $this->getUser();
        $devices = $deviceRepository->findBy(['client' => $user->getClient()]);

        $deviceTable = [];
        foreach ($devices as $device) {
            $data = $deviceDataRepository->findLastRecordForDevice($device);

            $online = false;
            if (time() - @strtotime($data->getDeviceDate()->format('Y-m-d H:i:s')) < 4200) {
                $online = true;
            }

            $deviceListModel = new DeviceListModel();
            $deviceListModel
                ->setId($device->getId())
                ->setName($device->getName())
                ->setOnline($online)
                ->setAlarm(false)
                ->setSignal($data->getGsmSignal())
                ->setPower(number_format($data->getVbat(), 1))
                ->setBattery($data->getBattery())
            ;

            $deviceTable[] = $deviceListModel;
        }

        return $this->render('device/list.html.twig', [
            'devices_table' => $deviceTable
        ]);
    }

    #[Route(path: '/read', name: 'app_home_read')]
    public function read(DeviceDataRepository $repository)
    {
        return $this->json($repository->findAll(), Response::HTTP_OK, [], ['groups' => 'device_read']);
    }

}