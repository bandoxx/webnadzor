<?php

namespace App\Controller\Client;

use App\Entity\ClientStorage;
use App\Repository\ClientStorageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class ImageController extends AbstractController
{

    public function __construct(
        private readonly SluggerInterface      $slugger,
        private readonly ParameterBagInterface $parameterBag,
    ) {}

    #[Route('/image/upload', name: 'image_upload_form')]
    public function uploadForm(): Response
    {
        return $this->render('device/upload.html.twig');
    }

    #[Route('/clientstorage/{clientId}/all', name: 'image_index')]
    public function index($clientId,ClientStorageRepository $clientStorageRepository): Response
    {
        $clientStorages = $clientStorageRepository->findBy(['client_id' => $clientId]);
        return $this->render('device/index.html.twig',['clientStorages' => $clientStorages]);
    }

    #[Route('/image/save', name: 'image_save', methods: ['POST'])]
    public function saveImage(Request $request, EntityManagerInterface $entityManager,KernelInterface $kernel)
    {
        $client_id = $request->request->get('clientId');
        $imageFile = $request->files->get('image');

        $publicDir = $kernel->getProjectDir() . '/public';

        if ($imageFile) {
            $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $this->slugger->slug($originalFilename);
            $newFilename = sprintf("%s-%s.%s", $safeFilename, uniqid(), $imageFile->guessExtension());

            try {
                $imageFile->move(
                    $publicDir.$this->parameterBag->get('images_directory'),
                    $newFilename
                );
            } catch (FileException $e) {
                return new JsonResponse(['status' => 'error', 'message' => 'File upload error: ' . $e->getMessage()]);
            }

            // Create and save the CustomerDevice entity
            $clientDevice = new ClientStorage();
            $clientDevice->setClientId($client_id);
            $clientDevice->setBaseImage($this->parameterBag->get('images_directory').$newFilename);
            $clientDevice->setName($newFilename);
            $clientDevice->setCreatedAt(new \DateTime());
            $clientDevice->setUpdatedAt(new \DateTime());

            $entityManager->persist($clientDevice);
            $entityManager->flush();

            // Redirect to the devices page
            return $this->redirectToRoute('client_devices_overview', ['clientId' => $client_id,'clientStorageId' => $clientDevice->getId()]);
        }
        return new JsonResponse(['status' => 'error', 'message' => 'No image uploaded.'], 400);
    }
}
