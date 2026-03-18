<?php

namespace App\Controller;

use App\Form\ProfileType;
use App\Form\ChangePasswordType;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use App\Entity\Client;

#[Route('/espace', name: 'profile_')]
#[IsGranted('ROLE_CLIENT')]
class ProfileController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(OrderRepository $orderRepository): Response
    {
        /** @var Client|null $user */
        $user = $this->getUser();
        if (!$user || !$user instanceof Client) {
            throw new AccessDeniedException('Utilisateur non authentifié');
        }

        $orders = $orderRepository->findBy(['client' => $user], ['createdAt' => 'DESC']);

        // Calculate units remaining as the sum of ordered quantities (fallback to 0)
        $unitsRemaining = 0;
        foreach ($orders as $o) {
            $q = $o->getQuantity();
            $unitsRemaining += $q ?? 0;
        }

        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'orders' => $orders,
            'unitsRemaining' => $unitsRemaining,
        ]);
    }

    #[Route('/edit', name: 'edit', methods: ['GET','POST'])]
    public function edit(Request $request, EntityManagerInterface $em): Response
    {
        /** @var Client|null $user */
        $user = $this->getUser();
        if (!$user || !$user instanceof Client) {
            throw new AccessDeniedException('Utilisateur non authentifié');
        }

        $form = $this->createForm(ProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($user);
            $em->flush();
            $this->addFlash('success', 'Profil mis à jour.');

            return $this->redirectToRoute('profile_index');
        }

        return $this->render('profile/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/change-password', name: 'change_password', methods: ['GET','POST'])]
    public function changePassword(Request $request, UserPasswordHasherInterface $hasher, EntityManagerInterface $em): Response
    {
        /** @var Client|null $user */
        $user = $this->getUser();
        if (!$user || !$user instanceof Client || !$user instanceof PasswordAuthenticatedUserInterface) {
            throw new AccessDeniedException('Utilisateur non authentifié ou incompatible');
        }

        $form = $this->createForm(ChangePasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $current = $data['currentPassword'] ?? null;
            $plain = $data['plainPassword'] ?? null;

            if (!$hasher->isPasswordValid($user, $current)) {
                $this->addFlash('error', 'Mot de passe actuel invalide.');
            } else {
                $hashed = $hasher->hashPassword($user, $plain);
                $user->setPassword($hashed);
                $em->persist($user);
                $em->flush();
                $this->addFlash('success', 'Mot de passe changé.');

                return $this->redirectToRoute('profile_index');
            }
        }

        return $this->render('profile/change_password.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/orders', name: 'orders', methods: ['GET'])]
    public function orders(OrderRepository $orderRepository): Response
    {
        /** @var Client|null $user */
        $user = $this->getUser();
        if (!$user || !$user instanceof Client) {
            throw new AccessDeniedException('Utilisateur non authentifié');
        }

        $orders = $orderRepository->findBy(['client' => $user], ['createdAt' => 'DESC']);

        return $this->render('profile/orders.html.twig', [
            'orders' => $orders,
        ]);
    }
}
