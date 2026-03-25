<?php

namespace App\Controller;

use App\Entity\Client;
use App\Entity\User;
use App\Entity\Order;
use App\Entity\Offer;
use App\Form\OrderType;
use App\Repository\OrderRepository;
use App\Repository\UnitRepository;
use App\Repository\OfferRepository;
use App\Service\PriceCalculator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Form\FormError;

#[Route('/order')]
final class OrderController extends AbstractController
{
    #[Route('/', name: 'app_order')]
    public function index(OrderRepository $orderRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!($user instanceof Client)) {
            $this->addFlash('error', 'Vous devez être connecté en tant que client pour accéder au dashboard.');
            return $this->redirectToRoute('app_login');
        }

        $orders = $orderRepository->findBy(['client' => $user], ['createdAt' => 'DESC']);

        return $this->render('order/index.html.twig', ['controller_name' => 'OrderController', 'orders' => $orders]);
    }

    #[Route('/new', name: 'app_order_new')]
    public function new(Request $request, EntityManagerInterface $em, UnitRepository $unitRepository, PriceCalculator $calculator, OfferRepository $offerRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!($user instanceof Client)) {
            $this->addFlash('error', 'Vous devez être connecté en tant que client pour passer une commande.');
            return $this->redirectToRoute('app_login');
        }

        // Pré-sélection d'une offre via ?offer={id}
        $offerId = $request->query->get('offer');
        $preselectedOffer = $offerId ? $offerRepository->find((int)$offerId) : null;

        $order = new Order();
        $order->setClient($user);
        if ($preselectedOffer) {
            if (null !== $preselectedOffer->getDiscountPercent()) {
                $order->setDiscountPercent($preselectedOffer->getDiscountPercent());
            }
            if (null !== $preselectedOffer->getMinUnits()) {
                $order->setQuantity($preselectedOffer->getMinUnits());
            }
        }

        $form = $this->createForm(OrderType::class, $order, ['compact' => (bool)$preselectedOffer, 'offer' => $preselectedOffer, 'allow_offer_select' => (bool)$preselectedOffer]);
        $form->handleRequest($request);

        $availableGlobal = $unitRepository->countAvailable();

        // Empêcher la réservation d'un nombre d'unités supérieur à la disponibilité, même si le formulaire est manipulé côté client
        if ($form->IsSubmitted() && $form->IsValid()) {
            $quantity = $order->getQuantity();
            if (null === $quantity || $quantity <= 0 || $availableGlobal > $quantity) {
                $form->get('quantity')->addError(new FormError('Veuillez indiquer une quantité valide.'));
                return $this->render('order/new.html.twig', ['form' => $form->createView(), 'available' => $availableGlobal, 'offers' => $offerRepository->findBy(['isActive' => true]), 'compact' => (bool)$preselectedOffer, 'selectedOffer' => $preselectedOffer, 'allow_offer_select' => (bool)$preselectedOffer]);
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $quantity = $order->getQuantity();
            if (null === $quantity || $quantity <= 0) {
                $form->get('quantity')->addError(new FormError('Veuillez indiquer une quantité valide.'));
                return $this->render('order/new.html.twig', ['form' => $form->createView(), 'available' => $availableGlobal, 'offers' => $offerRepository->findBy(['isActive' => true]), 'compact' => (bool)$preselectedOffer, 'selectedOffer' => $preselectedOffer, 'allow_offer_select' => (bool)$preselectedOffer]);
            }

            $offer = $order->getOffer() ?? $preselectedOffer;

            // Empêcher l'utilisation d'une offre inactive
            if ($offer instanceof Offer && !$offer->isActive()) {
                $this->addFlash('error', 'Cette offre n\'est plus active et ne peut pas être utilisée pour une nouvelle commande.');
                return $this->render('order/new.html.twig', [
                    'form' => $form->createView(),
                    'available' => $unitRepository->countAvailable($offer),
                    'offers' => $offerRepository->findBy([
                        'isActive' => true
                    ]),
                    'compact' => (bool)$preselectedOffer,
                    'selectedOffer' => $preselectedOffer,
                    'allow_offer_select' => (bool)$preselectedOffer
                ]);
            }

            // Calcul prix unitaire et total côté serveur
            $calculatedUnitPrice = $calculator->calculateUnitPrice($offer, $order->isAnnualPayment());
            $order->setUnitPrice($calculatedUnitPrice);
            if (null === $order->getTotal()) {
                $order->setTotal($calculator->calculateTotal($calculatedUnitPrice, $quantity, $order->getDiscountPercent()));
            }

            // Vérifier disponibilité et préparer l'aperçu de confirmation (sans persister)
            $units = $unitRepository->findAvailable($offer, $quantity);
            if (count($units) < $quantity) {
                $this->addFlash('error', sprintf('Il n\'y a que %d unité(s) disponible(s).', count($units)));
                return $this->render('order/new.html.twig', [
                    'form' => $form->createView(),
                    'available' => $unitRepository->countAvailable($offer),
                    'compact' => (bool)$preselectedOffer,
                    'selectedOffer' => $preselectedOffer,
                    'allow_offer_select' => (bool)$preselectedOffer
                ]);
            }

            $unitIds = array_map(fn($u) => $u->getId(), $units);

            // Affiche la page de confirmation (prévisualisation) — la soumission réelle se fait dans submit()
            return $this->render('order/confirm.html.twig', [
                'offer' => $offer,
                'quantity' => $quantity,
                'unitPrice' => $calculatedUnitPrice,
                'annualPayment' => $order->isAnnualPayment(),
                'discountPercent' => $order->getDiscountPercent(),
                'total' => $order->getTotal(),
                'available' => $unitRepository->countAvailable($offer),
                'units' => $unitIds,
            ]);
        }

        return $this->render('order/new.html.twig', ['form' => $form->createView(), 'available' => $availableGlobal, 'offers' => $offerRepository->findBy(['isActive' => true]), 'compact' => (bool)$preselectedOffer, 'selectedOffer' => $preselectedOffer, 'allow_offer_select' => (bool)$preselectedOffer]);
    }

    #[Route('/confirm', name: 'app_order_confirm', methods: ['POST'])]
    public function confirm(Request $request, OfferRepository $offerRepository, UnitRepository $unitRepository, PriceCalculator $calculator): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!($user instanceof Client)) {
            $this->addFlash('error', 'Vous devez être connecté en tant que client pour passer une commande.');
            return $this->redirectToRoute('app_login');
        }

        // Lecture simple des paramètres envoyés depuis le formulaire
        $offerId = $request->request->get('offer');
        $quantity = (int) $request->request->get('quantity', 0);
        $annualPayment = (bool) $request->request->get('annualPayment', false);
        $discountPercent = $request->request->get('discountPercent');

        $offer = $offerId ? $offerRepository->find($offerId) : null;
        $available = $unitRepository->countAvailable($offer);

        if ($quantity <= 0) {
            $this->addFlash('error', 'Veuillez indiquer une quantité valide.');
            return $this->redirectToRoute('app_order_new');
        }

        if ($offer) {
            if (null !== $offer->getMinUnits() && $quantity < $offer->getMinUnits()) {
                $this->addFlash('error', sprintf('La quantité minimale pour cette offre est %d.', $offer->getMinUnits()));
                return $this->redirectToRoute('app_order_new');
            }
            if (null !== $offer->getMaxUnits() && $quantity > $offer->getMaxUnits()) {
                $this->addFlash('error', sprintf('La quantité maximale pour cette offre est %d.', $offer->getMaxUnits()));
                return $this->redirectToRoute('app_order_new');
            }
        }

        $unitPrice = $calculator->calculateUnitPrice($offer, $annualPayment);
        $total = $calculator->calculateTotal((string)$unitPrice, $quantity, $discountPercent);

        $units = $unitRepository->findAvailable($offer, $quantity);
        $unitIds = array_map(fn($u) => $u->getId(), $units);

        return $this->render('order/confirm.html.twig', ['offer' => $offer, 'quantity' => $quantity, 'unitPrice' => $unitPrice, 'annualPayment' => $annualPayment, 'discountPercent' => $discountPercent, 'total' => $total, 'available' => $available, 'units' => $unitIds]);
    }

    #[Route('/submit', name: 'app_order_submit', methods: ['POST'])]
    public function submit(Request $request, EntityManagerInterface $em, OfferRepository $offerRepository, UnitRepository $unitRepository, PriceCalculator $calculator): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!($user instanceof Client)) {
            $this->addFlash('error', 'Vous devez être connecté en tant que client pour passer une commande.');
            return $this->redirectToRoute('app_login');
        }

        $offerId = $request->request->get('offer');
        $quantity = (int) $request->request->get('quantity', 0);

        $offer = $offerId ? $offerRepository->find($offerId) : null;

        // Vérification de disponibilité
        $available = $unitRepository->countAvailable($offer);
        if ($quantity > $available) {
            $this->addFlash('error', sprintf('Il n\'y a que %d unité(s) disponible(s).', $available));
            return $this->redirectToRoute('app_order_new');
        }


         // validation simple carte (Luhn)
         $cardNumber = $request->request->get('card_number');
         if (!$cardNumber || !$this->isValidCardNumber($cardNumber)) {
             $this->addFlash('card_error', 'Numéro de carte invalide ou manquant.');
             return $this->redirectToRoute('app_order_new');
         }

        // Créer et persister l'ordre
        $order = new Order();
        $order->setClient($user);
        if ($offer) $order->setOffer($offer);
        $order->setQuantity($quantity);

        $calculatedUnitPrice = $calculator->calculateUnitPrice($offer, (bool)$request->request->get('annualPayment'));
        $order->setUnitPrice($calculatedUnitPrice);
        $order->setAnnualPayment((bool)$request->request->get('annualPayment'));
        $order->setDiscountPercent($request->request->get('discountPercent'));
        $order->setTotal($calculator->calculateTotal((string)$calculatedUnitPrice, $quantity, $order->getDiscountPercent()));

        $units = $unitRepository->findAvailable($offer, $quantity);
        foreach ($units as $unit) {
            $unit->setOrders($order);
            $unit->setIsFree(false);
            $em->persist($unit);
        }

        $em->persist($order);
        $em->flush();

        $this->addFlash('success', 'Commande créée et unités allouées.');
        return $this->redirectToRoute('app_order');
    }

    #[Route('/available/{id}', name: 'app_order_available', methods: ['GET'])]
    public function available(Offer $offer, UnitRepository $unitRepository): JsonResponse
    {
        $count = $unitRepository->countAvailable($offer);

        return $this->json(['available' => $count]);
    }

    private function isValidCardNumber(string $number): bool
    {
        // remove non-digits
        $num = preg_replace('/\D+/', '', $number);
        if ('' === $num) return false;

        $sum = 0;
        $alt = false;
        for ($i = strlen($num) - 1; $i >= 0; $i--) {
            $n = (int)$num[$i];
            if ($alt) {
                $n *= 2;
                if ($n > 9) {
                    $n -= 9;
                }
            }
            $sum += $n;
            $alt = !$alt;
        }
        return $sum % 10 === 0;
    }
}
