<?php

namespace App\Controller\UnresolvedXml\API;

use App\Entity\UnresolvedDeviceData;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/api/unresolved-xml/{id}', name: 'admin_unresolved_xml_view', methods: ['GET'])]
class UnresolvedXmlViewController extends AbstractController
{

    public function __invoke(UnresolvedDeviceData $unresolvedXML): JsonResponse
    {
        return $this->json([
            'content' => $unresolvedXML->getContent()
        ]);
    }

}