<?php

namespace App\Service\ClientStorage;

use App\Entity\Client;
use App\Entity\ClientStorage;
use App\Service\Image\ClientStorageUploader;
use Symfony\Component\HttpFoundation\Request;

class ClientStorageHandler
{

    public function __construct(
        private ClientStorageUpdater $clientStorageUpdater,
        private ClientStorageUploader $clientStorageUploader
    ) {}


    public function update(ClientStorage $clientStorage, Request $request)
    {
        $inputs = $request->request->all();

        if (isset($inputs['text']['option'])) {
            $this->clientStorageUpdater->updateTextInputs($clientStorage, $inputs['text']);
        }

        if (isset($inputs['device']['option'])) {
            $this->clientStorageUpdater->updateDeviceInputs($clientStorage, $inputs['device']);
        }

        $image = $request->files->get('clientStorageImage');

        if ($image) {
            $this->clientStorageUploader->uploadAndSave($image, $clientStorage);
        }
    }

    public function getDropDown(Client $client): array
    {
        $devices = $client->getDevice()->toArray();
        $list = [];

        foreach ($devices as $device) {
            for ($entry = 1; $entry <= 2; $entry++) {
                $text = sprintf("%s, %s, %s", $device->getName(), $device->getEntryData($entry)['t_location'], $device->getEntryData($entry)['t_name']);
                if ($device->isTUsed($entry)) {
                    $list[] = [
                        'value' => sprintf("%s-%s-t", $device->getId(), $entry),
                        'text' => sprintf("%s - Temperatura", $text),
                    ];
                }

                if ($device->isRhUsed($entry)) {
                    $list[] = [
                        'value' => sprintf("%s-%s-rh", $device->getId(), $entry),
                        'text' => sprintf("%s - Vlaga", $text),
                    ];
                }
            }
        }

        return $list;
    }
}