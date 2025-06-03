<?php

namespace App\Controller\Device\API;

use App\Entity\Client;
use App\Factory\DeviceFactory;
use App\Repository\ClientRepository;
use App\Repository\DeviceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/api/{clientId}/device', name: 'api_device_create', methods: 'POST')]
class DeviceCreateController extends AbstractController
{

    public function __invoke(
        #[MapEntity(id: 'clientId')]
        Client $client,
        Request $request,
        DeviceRepository $deviceRepository,
        DeviceFactory $deviceFactory,
        EntityManagerInterface $entityManager
    ): RedirectResponse|NotFoundHttpException
    {
        $xmlName = $request->request->get('xmlName', '');
        $serialNumber = $request->request->get('serialNumber', '');

        if (empty($xmlName) && empty($serialNumber)) {
            $this->addFlash('error', "Popunite bar jedno od dva polja da bi dodali lokaciju!");
            return $this->redirectToRoute('app_device_list', ['clientId' => $client->getId()]);
        }

        if (!empty($xmlName) && $deviceRepository->doesMoreThenOneXmlNameExists($xmlName)) {
            $this->addFlash('error', sprintf("Xml naziv: `%s` već postoji!", $xmlName));
            return $this->redirectToRoute('app_device_list', ['clientId' => $client->getId()]);
        }

        if (!empty($serialNumber) && $deviceRepository->doesMoreThanOneSerialNumberExists($serialNumber)) {
            $this->addFlash('error', sprintf("Serijski broj: `%s` već postoji!", $serialNumber));
            return $this->redirectToRoute('app_device_list', ['clientId' => $client->getId()]);
        }

        $device = $deviceFactory->create($client, $xmlName, $serialNumber);
        $entityManager->persist($device);
        $entityManager->flush();

        return $this->redirectToRoute('app_device_list', ['clientId' => $client->getId()]);

    }

}