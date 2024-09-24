<?php

namespace App\Controller\Overview\API;

use App\Entity\Client;
use App\Entity\User;
use App\Service\Client\ClientRemover;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/api/client/{clientId}/delete', name: 'api_client_delete', methods: 'POST')]
class ClientDeleteController extends AbstractController
{

    public function __invoke(
        #[MapEntity(id: 'clientId')]
        Client $client,
        Request $request,
        ClientRemover $clientRemover,
        UserPasswordHasherInterface $hasher
    ): RedirectResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($user->isRoot() === false) {
            $this->addFlash('error', 'Nemate prava za brisanje klijenata.');

            return $this->redirectToOverview();
        }

        if (!$hasher->isPasswordValid($user, $request->request->get('password_check', ''))) {
            $this->addFlash('error', 'Pogrešna lozinka.');

            return $this->redirectToOverview();
        }

        $clientRemover->remove($client, $user);

        return $this->redirectToOverview();
    }

    private function redirectToOverview(): RedirectResponse
    {
        return $this->redirectToRoute('admin_overview');
    }
}