<?php

namespace App\Controller\UnresolvedXml;

use App\Repository\UnresolvedDeviceDataRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/unresolved-xml', name: 'admin_unresolved_xml', methods: ['GET'])]
class UnresolvedXmlListController extends AbstractController
{

    public function __invoke(UnresolvedDeviceDataRepository $unresolvedXMLRepository): Response
    {
        return $this->render('v2/unresolved_xml/table.html.twig', [
            'unresolved_xmls' => $unresolvedXMLRepository->findWithoutContent(),
        ]);
    }

}