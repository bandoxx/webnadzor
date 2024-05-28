<?php

namespace App\Controller\SMTP;

use App\Repository\SmtpRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\RouterInterface;

#[Route(path: '/api/smtp', name: 'api_smtp_update', methods: 'POST')]
class UpdateSMTPController extends AbstractController
{

    public function __invoke(Request $request, SmtpRepository $smtpRepository, RouterInterface $router, EntityManagerInterface $entityManager)
    {
        $smtp = $smtpRepository->findOneBy([]);
        $data = $request->request->all();

        if (!$smtp) {
            return new RedirectResponse($router->generate('admin_overview'));
        }

        $smtp->setHost($data['smtp_server']);
        $smtp->setPort($data['smtp_port']);
        $smtp->setUsername($data['smtp_username']);
        $smtp->setPassword($data['smtp_password']);

        $entityManager->flush();

        return new RedirectResponse($router->generate('admin_overview'));
    }

}