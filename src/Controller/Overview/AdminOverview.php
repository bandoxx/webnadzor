<?php

namespace App\Controller\Overview;

use App\Entity\User;
use App\Repository\SmtpRepository;
use App\Service\Overview\AdminOverviewService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/overview', name: 'admin_overview')]
class AdminOverview extends AbstractController
{
    public function __invoke(
        SmtpRepository $smtpRepository,
        AdminOverviewService $overviewService
    ): RedirectResponse|\Symfony\Component\HttpFoundation\Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Decide redirect if needed (single client cases)
        $redirectClientId = $overviewService->getRedirectClientId($user);
        if ($redirectClientId !== null) {
            return $this->redirectToRoute('client_overview', [
                'clientId' => $redirectClientId,
            ]);
        }

        $data = $overviewService->buildOverview($user);

        return $this->render('v2/overview/admin.html.twig', [
            'clients' => $data,
            'smtp' => $smtpRepository->findOneBy([])
        ]);
    }
}