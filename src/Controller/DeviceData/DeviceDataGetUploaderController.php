<?php

namespace App\Controller\DeviceData;

use App\Entity\Device;
use App\Entity\DeviceData;
use App\Factory\DeviceDataFactory;
use App\Factory\UnresolvedDeviceDataFactory;
use App\Repository\DeviceRepository;
use App\Service\ClientStorage\Types\DeviceTypesDropdown;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DeviceDataGetUploaderController extends AbstractController
{
    public function __construct(
        private DeviceRepository $deviceRepository,
        private DeviceDataFactory $deviceDataFactory,
        private UnresolvedDeviceDataFactory $unresolvedDeviceDataFactory,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger
    ) {
    }

    #[Route('/api/xml-uploader', name: 'api_device_data_get_uploader', methods: ['GET'])]
    public function __invoke(Request $request): JsonResponse
    {
        $apiKey = $request->headers->get('X-API-KEY');

        if (empty($apiKey) || $apiKey !== 'B7X4N9P6T3Q8W1KF') {
            return $this->json('Token is not valid', Response::HTTP_UNAUTHORIZED);
        }

        $filename = $request->query->get('filename');
        $content = urldecode($request->query->get('content'));

        if (empty($content)) {
            return $this->json('Invalid request.', Response::HTTP_BAD_REQUEST);
        }

        try {
            // Extract device name from filename
            $deviceName = $filename;
            
            // Look up the device in the database
            $device = $this->deviceRepository->binaryFindOneByName($deviceName);
            
            if (!$device) {
                $this->logger->error(sprintf("Device with name %s doesn't exist!", $deviceName));
                $this->saveUnresolvedData($content, $filename);
                return $this->json('Data saved as unresolved.', Response::HTTP_OK);
            }
            
            if ($device->isParserActive() === false) {
                $this->logger->error(sprintf("Device with name %s is not currently active!", $deviceName));
                $this->saveUnresolvedData($content, $filename);
                return $this->json('Data saved as unresolved.', Response::HTTP_OK);
            }
            
            // Create a temporary file to use with the existing factory method
            $tempFile = tempnam(sys_get_temp_dir(), 'xml_');
            file_put_contents($tempFile, $content);
            
            // Create DeviceData entity from XML content
            $deviceData = $this->deviceDataFactory->createFromXml($device, $tempFile);
            
            // Remove temporary file
            unlink($tempFile);
            
            // Check if a record with the same device_id and device_date already exists
            $existingData = $this->entityManager->getRepository(DeviceData::class)->findOneBy([
                'device' => $device,
                'deviceDate' => $deviceData->getDeviceDate()
            ]);
            
            if ($existingData) {
                // Skip insertion if a duplicate is found
                $this->logger->info(sprintf(
                    "Skipping duplicate data for device %s with date %s", 
                    $deviceName, 
                    $deviceData->getDeviceDate()->format('Y-m-d H:i:s')
                ));
                return $this->json('Duplicate data skipped.', Response::HTTP_OK);
            }
            
            // Persist the DeviceData entity
            $this->entityManager->persist($deviceData);
            $this->entityManager->flush();
            
            return $this->json('Data saved successfully.', Response::HTTP_OK);
        } catch (\Exception $e) {
            $this->logger->error('Error processing XML data: ' . $e->getMessage());
            return $this->json('Error processing data: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/admin/device-data', name: 'device_data', methods: ['GET'])]
    public function deviceDataView(): Response
    {
        $devices = DeviceTypesDropdown::getAllDevices($this->deviceRepository);
        return $this->render('v2/device/device_data.html.twig', [
            'deviceTypesDropdown' => $devices,
        ]);
    }
    
    private function saveUnresolvedData(string $content, string $filename): void
    {
        $unresolvedData = $this->unresolvedDeviceDataFactory->createFromString($content);
        $unresolvedData->setXmlName($filename);
        $this->entityManager->persist($unresolvedData);
        $this->entityManager->flush();
    }
}