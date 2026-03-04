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
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

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

        $orders = $orderRepository->findBy(
            ['client' => $user],
            ['createdAt' => 'DESC']
        );

        return $this->render('order/index.html.twig', [
            'controller_name' => 'OrderController',
        ]);
    }

    #[Route('/new', name: 'app_order_new')]
    public function new(Request $request, EntityManagerInterface $em, UnitRepository $unitRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!($user instanceof Client)) {
            $this->addFlash('error', 'Vous devez être connecté en tant que client pour passer une commande.');
            return $this->redirectToRoute('app_login');
        }

        // détecte si l'utilisateur veut une simulation (fausse transaction)
        $simulate = false;
        $dry = $request->query->get('dry') ?? $request->request->get('dry');
        $sim = $request->query->get('simulate') ?? $request->request->get('simulate');
        if (null !== $dry && (string)$dry === '1') {
            $simulate = true;
        }
        if (null !== $sim && (string)$sim === '1') {
            $simulate = true;
        }

        $order = new Order();
        $order->setClient($user);

        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        // si le champ simulate est présent dans le formulaire, priorise sa valeur
        if ($form->has('simulate') && $form->get('simulate')->getData()) {
            $simulate = true;
        }

        // nombre d'unités disponibles pour affichage (global)
        $available = $unitRepository->countAvailable();

        if ($form->isSubmitted() && $form->isValid()) {
            $quantity = $order->getQuantity() ?? 0;
            $offer = $order->getOffer();

            // En mode simulation, on ne persiste pas : on calcule l'allocation et on affiche un aperçu
            if ($simulate) {
                // récupère $quantity unités disponibles (ou moins si pas assez)
                $units = $unitRepository->findAvailable($offer, $quantity);

                if (count($units) < $quantity) {
                    $this->addFlash('error', sprintf('Simulation : il n\'y a que %d unité(s) disponible(s).', count($units)));

                    return $this->render('order/new.html.twig', [
                        'form' => $form->createView(),
                        'available' => $unitRepository->countAvailable($offer),
                        'simulate' => true,
                        'simulatedUnits' => array_map(fn($u) => $u->getId(), $units),
                    ]);
                }

                // recalcul du total si nécessaire (dans la simulation aussi)
                if (null === $order->getTotal() && null !== $order->getQuantity() && null !== $order->getUnitPrice()) {
                    $total = bcmul((string)$order->getUnitPrice(), (string)$order->getQuantity(), 2);
                    $order->setTotal($total);
                }

                // Prépare les ids d'unités allouées pour l'aperçu
                $simulatedUnitIds = array_map(fn($u) => $u->getId(), $units);

                $this->addFlash('info', 'Simulation effectuée — aucune modification de la base de données.');

                return $this->render('order/new.html.twig', [
                    'form' => $form->createView(),
                    'available' => $unitRepository->countAvailable($offer),
                    'simulate' => true,
                    'simulatedUnits' => $simulatedUnitIds,
                    'simulatedTotal' => $order->getTotal(),
                ]);
            }

            try {
                $units = $unitRepository->findAvailable($offer, $quantity);

                if (count($units) < $quantity) {
                    $this->addFlash('error', sprintf('Il n\'y a que %d unité(s) disponible(s).', count($units)));

                    return $this->render('order/new.html.twig', [
                        'form' => $form->createView(),
                        'available' => $unitRepository->countAvailable($offer),
                    ]);
                }

                // recalcul du total si nécessaire
                if (null === $order->getTotal() && null !== $order->getQuantity() && null !== $order->getUnitPrice()) {
                    $total = bcmul((string)$order->getUnitPrice(), (string)$order->getQuantity(), 2);
                    $order->setTotal($total);
                }

                $em->persist($order);

                // assigner les units à la commande et marquer comme non libres
                foreach ($units as $unit) {
                    $unit->setOrders($order);
                    $unit->setIsFree(false);
                    $em->persist($unit);
                }

                $em->flush();

                $this->addFlash('success', 'Commande créée et unités allouées.');

                return $this->redirectToRoute('app_order');
            } catch (\Throwable $e) {
                // journaliser l'erreur si besoin
                $this->addFlash('error', 'Une erreur est survenue lors de la création de la commande.');

                return $this->render('order/new.html.twig', [
                    'form' => $form->createView(),
                    'available' => $unitRepository->countAvailable($offer),
                ]);
            }
        }

        return $this->render('order/new.html.twig', [
            'form' => $form->createView(),
            'available' => $available,
        ]);
    }

    #[Route('/confirm', name: 'app_order_confirm', methods: ['POST'])]
    public function confirm(Request $request, OfferRepository $offerRepository, UnitRepository $unitRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!($user instanceof Client)) {
            $this->addFlash('error', 'Vous devez être connecté en tant que client pour passer une commande.');
            return $this->redirectToRoute('app_login');
        }

        // Récupère les données envoyées par JS
        $offerId = $request->request->get('offer');
        $quantity = (int) $request->request->get('quantity');
        $unitPrice = $request->request->get('unitPrice');
        $annualPayment = (bool)$request->request->get('annualPayment');
        $discountPercent = $request->request->get('discountPercent');
        $total = $request->request->get('total');
        $createdAt = $request->request->get('createdAt');
        $simulate = (bool)$request->request->get('simulate');

        $offer = $offerId ? $offerRepository->find($offerId) : null;
        $available = $unitRepository->countAvailable($offer);

        // Si simulation, récupérer les unités proposées
        $simulatedUnits = [];
        if ($simulate && $quantity > 0) {
            $units = $unitRepository->findAvailable($offer, $quantity);
            $simulatedUnits = array_map(fn($u) => $u->getId(), $units);
        }

        return $this->render('order/confirm.html.twig', [
            'offer' => $offer,
            'quantity' => $quantity,
            'unitPrice' => $unitPrice,
            'annualPayment' => $annualPayment,
            'discountPercent' => $discountPercent,
            'total' => $total,
            'createdAt' => $createdAt,
            'available' => $available,
            'simulate' => $simulate,
            'simulatedUnits' => $simulatedUnits,
        ]);
    }

    #[Route('/submit', name: 'app_order_submit', methods: ['POST'])]
    public function submit(Request $request, EntityManagerInterface $em, OfferRepository $offerRepository, UnitRepository $unitRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!($user instanceof Client)) {
            $this->addFlash('error', 'Vous devez être connecté en tant que client pour passer une commande.');
            return $this->redirectToRoute('app_login');
        }

        $offerId = $request->request->get('offer');
        $quantity = (int) $request->request->get('quantity');
        $unitPrice = $request->request->get('unitPrice');
        $annualPayment = $request->request->get('annualPayment') ? true : false;
        $discountPercent = $request->request->get('discountPercent');
        $total = $request->request->get('total');
        $createdAtRaw = $request->request->get('createdAt');
        $simulate = $request->request->get('simulate') ? true : false;

        $offer = $offerId ? $offerRepository->find($offerId) : null;

        // Vérification de disponibilité
        $available = $unitRepository->countAvailable($offer);
        if ($quantity > $available) {
            $this->addFlash('error', sprintf('Il n\'y a que %d unité(s) disponible(s).', $available));
            return $this->redirectToRoute('app_order_new');
        }

        // En simulation, on ne persiste pas ; redirige vers la page new avec message (ou rester sur confirmation)
        if ($simulate) {
            $units = $unitRepository->findAvailable($offer, $quantity);
            $simulatedUnitIds = array_map(fn($u) => $u->getId(), $units);
            $this->addFlash('info', 'Simulation confirmée — aucune modification en base.');

            return $this->render('order/confirm.html.twig', [
                'offer' => $offer,
                'quantity' => $quantity,
                'unitPrice' => $unitPrice,
                'annualPayment' => $annualPayment,
                'discountPercent' => $discountPercent,
                'total' => $total,
                'createdAt' => $createdAtRaw,
                'available' => $available,
                'simulate' => true,
                'simulatedUnits' => $simulatedUnitIds,
            ]);
        }

        // Mode réel : créer l'entité Order, assigner unités, persister (sans transaction explicite)
        $order = new Order();
        $order->setClient($user);
        if ($offer) {
            $order->setOffer($offer);
        }
        $order->setQuantity($quantity);
        if (null !== $unitPrice) {
            $order->setUnitPrice($unitPrice);
        }
        $order->setAnnualPayment($annualPayment);
        if (null !== $discountPercent) {
            $order->setDiscountPercent($discountPercent);
        }
        if (null !== $total) {
            $order->setTotal($total);
        }
        if ($createdAtRaw) {
            try {
                $order->setCreatedAt(new \DateTimeImmutable($createdAtRaw));
            } catch (\Throwable $e) {
                // ignore format and use default
            }
        }

        try {
            // assigner unités
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
        } catch (\Throwable $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de la création de la commande.');
            return $this->redirectToRoute('app_order_new');
        }
    }

    #[Route('/available/{id}', name: 'app_order_available', methods: ['GET'])]
    public function available(Offer $offer, UnitRepository $unitRepository): JsonResponse
    {
        $count = $unitRepository->countAvailable($offer);

        return $this->json(['available' => $count]);
    }
}
