<?php

namespace App\Controller;

use App\Entity\Client;
use App\Entity\User;
use App\Repository\OrderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;


#[Route('/dashboard', name: 'app_dashboard')]
final class DashboardController extends AbstractController
{
    #[Route('/', name: '')]
    public function index(OrderRepository $orderRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!($user instanceof Client)) {
            $this->addFlash('error', 'Vous devez être connecté en tant que client pour accéder au dashboard.');
            return $this->redirectToRoute('app_login');
        }

        $orders = $orderRepository->findBy(
            ['client' => $user],
            ['createdAt' => 'DESC'],
            6
        );

        return $this->render('dashboard/index.html.twig', [
            'orders' => $orders,
            'controller_name' => 'DashboardController',
        ]);
    }
}
