<?php

namespace App\Controller;

use App\Entity\Ticket;
use App\Entity\Client;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/ticket', name: 'app_ticket')]
final class TicketController extends AbstractController
{
    #[Route('/', name: 'app_ticket_index')]
    public function index(): Response
    {
        return $this->render('ticket/index.html.twig', [
            'controller_name' => 'TicketController',
        ]);
    }

    #[Route('/new', name: 'app_ticket_new')]
    public function new(): Response {
        // s'assurer que l'utilisateur connecté est bien un Client
        $user = $this->getUser();
        if (!($user instanceof Client)) {
            $this->addFlash('error', 'Vous devez être connecté en tant que client pour créer un ticket.');
            return $this->redirectToRoute('app_login');
        }

        $ticket = new Ticket();
        $ticket->setClient($user);

        return $this->render('ticket/new.html.twig', [
            'controller_name' => 'TicketController',
            'ticket' => $ticket,
        ]);
    }
}
