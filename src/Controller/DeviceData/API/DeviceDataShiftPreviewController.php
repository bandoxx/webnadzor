<?php

namespace App\Controller\DeviceData\API;

use App\Service\DeviceData\ShiftDeviceDataService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
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
        // Get and validate query parameters
        $deviceId = $request->query->get('deviceId');
        $dateFrom = $request->query->get('dateFrom');
        $dateTo = $request->query->get('dateTo');
        $intervalDays = $request->query->get('intervalDays', 25);

        // Validate required parameters
        if (!$deviceId) {
            throw new BadRequestHttpException('Missing required parameter: deviceId');
        }

        if (!$dateFrom) {
            throw new BadRequestHttpException('Missing required parameter: dateFrom');
        }

        if (!$dateTo) {
            throw new BadRequestHttpException('Missing required parameter: dateTo');
        }

        // Validate and parse device ID
        if (!is_numeric($deviceId) || (int)$deviceId <= 0) {
            throw new BadRequestHttpException('Invalid deviceId: must be a positive integer');
        }
        $deviceId = (int)$deviceId;

        // Validate and parse interval days
        if (!is_numeric($intervalDays) || (int)$intervalDays <= 0) {
            throw new BadRequestHttpException('Invalid intervalDays: must be a positive integer');
        }
        $intervalDays = (int)$intervalDays;

        // Parse dates
        try {
            $dateFromObj = new \DateTime($dateFrom);
            $dateToObj = new \DateTime($dateTo);
        } catch (\Exception $e) {
            throw new BadRequestHttpException('Invalid date format. Expected format: Y-m-d H:i:s or Y-m-d');
        }

        // Validate date range
        if ($dateFromObj > $dateToObj) {
            throw new BadRequestHttpException('dateFrom must be before or equal to dateTo');
        }

        // Validate dateTo is not in the future
        $now = new \DateTime();
        if ($dateToObj > $now) {
            throw new BadRequestHttpException('dateTo cannot be in the future');
        }

        try {
            // Get preview data
            $previewData = $this->shiftDeviceDataService->previewShiftedData(
                $deviceId,
                $dateFromObj,
                $dateToObj,
                $intervalDays
            );

            return $this->json([
                'success' => true,
                'message' => 'Preview data retrieved successfully',
                'data' => [
                    'deviceId' => $deviceId,
                    'dateFrom' => $dateFromObj->format('Y-m-d H:i:s'),
                    'dateTo' => $dateToObj->format('Y-m-d H:i:s'),
                    'intervalDays' => $intervalDays,
                    'recordCount' => count($previewData),
                    'records' => $previewData,
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
