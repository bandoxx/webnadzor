<?php

namespace App\Controller\UnresolvedXml\API;

use App\Entity\UnresolvedDeviceData;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/api/unresolved-xml/{id}', name: 'admin_unresolved_xml_delete', methods: ['DELETE'])]
class UnresolvedXmlDeleteController extends AbstractController
{

    public function __invoke(UnresolvedDeviceData $unresolvedXML, EntityManagerInterface $entityManager): JsonResponse
    {
        $entityManager->remove($unresolvedXML);
        $entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

}