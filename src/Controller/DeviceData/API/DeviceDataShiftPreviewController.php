<?php

namespace App\Controller\DeviceData\API;

use App\Entity\User;
use App\Service\DeviceData\ShiftDeviceDataService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(
    path: '/api/device-data/shift/preview',
    name: 'api_device_data_shift_preview',
    methods: ['GET']
)]
class DeviceDataShiftPreviewController extends AbstractController
{
    public function __construct(
        private readonly ShiftDeviceDataService $shiftDeviceDataService
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($user->getPermission() !== 4) {
            throw $this->createAccessDeniedException();
        }
        // Get and validate query parameters
        $deviceId = $request->query->get('deviceId');
        $dateFrom = $request->query->get('dateFrom');
        $dateTo = $request->query->get('dateTo');

        // Validate required parameters
        if (!$deviceId) {
            return $this->json([
                'success' => false,
                'message' => 'Morate izabrati uređaj.',
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!$dateFrom) {
            return $this->json([
                'success' => false,
                'message' => 'Morate uneti datum od.',
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!$dateTo) {
            return $this->json([
                'success' => false,
                'message' => 'Morate uneti datum do.',
            ], Response::HTTP_BAD_REQUEST);
        }

        // Validate and parse device ID
        if (!is_numeric($deviceId) || (int)$deviceId <= 0) {
            return $this->json([
                'success' => false,
                'message' => 'Neispravan ID uređaja.',
            ], Response::HTTP_BAD_REQUEST);
        }
        $deviceId = (int)$deviceId;

        // Parse dates
        try {
            $dateFromObj = new \DateTime($dateFrom);
            $dateToObj = new \DateTime($dateTo);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Neispravan format datuma. Očekivani format: dd.mm.yyyy',
            ], Response::HTTP_BAD_REQUEST);
        }

        // Validate date range
        if ($dateFromObj > $dateToObj) {
            return $this->json([
                'success' => false,
                'message' => 'Datum od mora biti pre datuma do.',
            ], Response::HTTP_BAD_REQUEST);
        }

        // Cap dateTo to yesterday (don't insert data for today or future)
        $yesterday = (new \DateTime())->modify('-1 day')->setTime(23, 59, 59);
        $dateToWasCapped = false;
        if ($dateToObj > $yesterday) {
            $dateToObj = $yesterday;
            $dateToWasCapped = true;
        }

        // Validate that dateFrom is not after capped dateTo
        if ($dateFromObj > $dateToObj) {
            return $this->json([
                'success' => false,
                'message' => 'Datum od ne može biti u budućnosti. Maksimalni datum je jučerašnji dan.',
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            // Get preview data (automatically finds best interval 20-35 days)
            $previewData = $this->shiftDeviceDataService->previewShiftedData(
                $deviceId,
                $dateFromObj,
                $dateToObj
            );

            return $this->json([
                'success' => true,
                'message' => 'Preview data retrieved successfully',
                'data' => [
                    'deviceId' => $deviceId,
                    'dateFrom' => $dateFromObj->format('Y-m-d H:i:s'),
                    'dateTo' => $dateToObj->format('Y-m-d H:i:s'),
                    'dateToWasCapped' => $dateToWasCapped,
                    'intervalDays' => $previewData['intervalDays'],
                    'recordCount' => count($previewData['records']),
                    'records' => $previewData['records'],
                ],
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Error retrieving preview data: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
