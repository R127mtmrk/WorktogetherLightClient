<?php

namespace App\Controller;

use App\Entity\Offer;
use App\Form\OfferType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class OfferController extends AbstractController
{
    #[Route('/offer/new', name: 'app_offer_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        // Seuls les administrateurs peuvent créer/éditer des offres
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $offer = new Offer();
        $form = $this->createForm(OfferType::class, $offer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($offer);
            $em->flush();
            $this->addFlash('success', 'Offre créée.');
            return $this->redirectToRoute('app_offer_new');
        }

        return $this->render('offer/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
