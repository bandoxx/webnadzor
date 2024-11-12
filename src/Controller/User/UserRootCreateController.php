<?php

namespace App\Controller\User;

use App\Entity\User;
use App\Factory\UserFactory;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/api/user', name: 'app_root_user_create', methods: 'POST')]
class UserRootCreateController extends AbstractController
{

    public function __invoke(Request $request, EntityManagerInterface $entityManager, UserFactory $userFactory, UserRepository $userRepository): RedirectResponse
    {
        $username = $request->request->get('username');

        if ($userRepository->findOneByUsername($username)) {
            $this->addFlash('error', sprintf('Korisnik sa nazivom %s već postoji.', $username));

            return $this->redirectToBase();
        }

        $password = $request->request->get('password');
        $passwordRepeat = $request->request->get('password_again');

        if ($password !== $passwordRepeat) {
            $this->addFlash('error', 'Zaporke se ne podudaraju.');

            return $this->redirectToBase();
        }

        $user = $userFactory->create(
            $username,
            $password,
            User::ROLE_ROOT,
        );

        $entityManager->persist($user);
        $entityManager->flush();

        return $this->redirectToBase();
    }

    private function redirectToBase(): RedirectResponse
    {
        return $this->redirectToRoute('app_admin_get_root_users');
    }

}