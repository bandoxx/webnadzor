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
    path: '/api/device-data/shift/insert',
    name: 'api_device_data_shift_insert',
    methods: ['POST']
)]
class DeviceDataShiftInsertController extends AbstractController
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
        // Parse JSON body
        $data = json_decode($request->getContent(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->json([
                'success' => false,
                'message' => 'Neispravan format zahteva.',
            ], Response::HTTP_BAD_REQUEST);
        }

        // Get and validate parameters from request body
        $deviceId = $data['deviceId'] ?? null;
        $dateFrom = $data['dateFrom'] ?? null;
        $dateTo = $data['dateTo'] ?? null;
        $intervalDays = $data['intervalDays'] ?? null;
        $entry = $data['entry'] ?? null; // Optional: 1 or 2 for per-entry filling

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

        if ($intervalDays === null) {
            return $this->json([
                'success' => false,
                'message' => 'Molimo prvo kliknite "Prikaži" za pronalaženje podataka.',
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

        // Validate and parse interval days
        if (!is_numeric($intervalDays) || (int)$intervalDays <= 0) {
            return $this->json([
                'success' => false,
                'message' => 'Neispravan interval dana.',
            ], Response::HTTP_BAD_REQUEST);
        }
        $intervalDays = (int)$intervalDays;

        // Validate and parse entry (optional: 1 or 2 for per-entry filling, null for both)
        $entryInt = null;
        if ($entry !== null) {
            if (!is_numeric($entry) || !in_array((int)$entry, [1, 2], true)) {
                return $this->json([
                    'success' => false,
                    'message' => 'Neispravan broj ulaza. Dozvoljene vrijednosti: 1 ili 2.',
                ], Response::HTTP_BAD_REQUEST);
            }
            $entryInt = (int)$entry;
        }

        // Parse dates (set time to start/end of day for full day coverage)
        try {
            $dateFromObj = (new \DateTime($dateFrom))->setTime(0, 0, 0);
            $dateToObj = (new \DateTime($dateTo))->setTime(23, 59, 59);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Neispravan format datuma.',
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
        if ($dateToObj > $yesterday) {
            $dateToObj = $yesterday;
        }

        // Validate that dateFrom is not after capped dateTo
        if ($dateFromObj > $dateToObj) {
            return $this->json([
                'success' => false,
                'message' => 'Datum od ne može biti u budućnosti. Maksimalni datum je jučerašnji dan.',
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            // Insert shifted data
            $insertedCount = $this->shiftDeviceDataService->insertShiftedData(
                $deviceId,
                $dateFromObj,
                $dateToObj,
                $intervalDays,
                $entryInt
            );

            $message = "Uspješno uneseno/ažurirano {$insertedCount} zapisa";

            return $this->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'deviceId' => $deviceId,
                    'dateFrom' => $dateFromObj->format('Y-m-d H:i:s'),
                    'dateTo' => $dateToObj->format('Y-m-d H:i:s'),
                    'intervalDays' => $intervalDays,
                    'insertedCount' => $insertedCount,
                    'entry' => $entryInt,
                ],
            ], Response::HTTP_CREATED);
        } catch (\InvalidArgumentException $e) {
            return $this->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Error inserting shifted data: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
