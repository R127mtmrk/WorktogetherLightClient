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

        $orders = $orderRepository->findBy(
            ['client' => $user],
            ['createdAt' => 'DESC']
        );

        return $this->render('order/index.html.twig', [
            'controller_name' => 'OrderController',
            'orders' => $orders,
        ]);
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

        $content = $request->getContent();
        $parsedJson = null;
        if ($content && empty($request->request->all())) {
            $decoded = json_decode($content, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $parsedJson = $decoded;
                // si l'objet est encapsulé dans `order`, utiliser ce sous-tableau
                $toSubmit = $decoded['order'] ?? $decoded;
                $form->submit($toSubmit);
            } else {
                // fallback au comportement standard si non-JSON
                $form->handleRequest($request);
            }
        } else {
            $form->handleRequest($request);
        }

        // si le champ simulate est présent dans le formulaire, priorise sa valeur
        if ($form->has('simulate') && $form->get('simulate')->getData()) {
            $simulate = true;
        }

        // nombre d'unités disponibles pour affichage (global)
        $available = $unitRepository->countAvailable();

        // Fallback mapping : si le Form n'a pas mappé la quantité (NULL), essayer de la récupérer
        // depuis le POST brut (soit order[quantity], soit quantity) — utile si JS a modifié le payload.
        if ($request->isMethod('POST') && $order->getQuantity() === null) {
            $posted = $request->request->get('order');
            $quantityPosted = null;
            if (is_array($posted) && array_key_exists('quantity', $posted)) {
                $quantityPosted = $posted['quantity'];
            } elseif ($request->request->has('quantity')) {
                $quantityPosted = $request->request->get('quantity');
            } else {
                // si Request->request est vide (par ex. Content-Type non standard), tenter de parser le body brut
                $content = $request->getContent();
                if ($content) {
                    $parsedBody = [];
                    parse_str($content, $parsedBody);
                    if (isset($parsedBody['order']) && is_array($parsedBody['order']) && isset($parsedBody['order']['quantity'])) {
                        $quantityPosted = $parsedBody['order']['quantity'];
                    } elseif (isset($parsedBody['quantity'])) {
                        $quantityPosted = $parsedBody['quantity'];
                    }
                }
            }

            if (null !== $quantityPosted && trim((string)$quantityPosted) !== '') {
                $order->setQuantity((int)$quantityPosted);
            } else {
                // Quantité introuvable dans le POST ; informer l'utilisateur et rediriger proprement
                $this->addFlash('error', 'Quantité manquante ou invalide dans le formulaire.');
                return $this->redirectToRoute('app_order_new');
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            // Récupérer la quantité telle que mappée par le formulaire
            $quantityRaw = $order->getQuantity();
            if (null === $quantityRaw) {
                // ajouter une erreur sur le champ et réafficher le formulaire
                $form->get('quantity')->addError(new FormError('Veuillez indiquer une quantité valide.'));

                return $this->render('order/new.html.twig', [
                    'form' => $form->createView(),
                    'available' => $available,
                ]);
            }

            $quantity = $quantityRaw;
            $offer = $order->getOffer();

            // Empêcher l'utilisation d'une offre inactive si l'utilisateur a sélectionné manuellement une offre inactive (par POST ou manipulation)
            if ($offer instanceof Offer && !$offer->isActive()) {
                // autoriser l'affichage / simulation mais empêcher la création réelle
                if (!$simulate) {
                    $this->addFlash('error', 'Cette offre n\'est plus active et ne peut pas être utilisée pour une nouvelle commande.');

                    return $this->render('order/new.html.twig', [
                        'form' => $form->createView(),
                        'available' => $unitRepository->countAvailable($offer),
                    ]);
                }
            }

            // Calculate unit price using calculator
            $calculatedUnitPrice = $calculator->calculateUnitPrice($offer, $order->isAnnualPayment());
            $order->setUnitPrice($calculatedUnitPrice);

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

                // recalcul du total via service
                if (null === $order->getTotal() && null !== $order->getQuantity()) {
                    $total = $calculator->calculateTotal($order->getUnitPrice(), $order->getQuantity(), $order->getDiscountPercent());
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

                // recalcul du total via service
                if (null === $order->getTotal() && null !== $order->getQuantity()) {
                    $total = $calculator->calculateTotal($order->getUnitPrice(), $order->getQuantity(), $order->getDiscountPercent());
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
            'offers' => $offerRepository->findBy(['isActive' => true]),
        ]);
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

        // Extraire proprement les paramètres (support order[...] / champs plats / JSON)
        $offerId = $this->extractParam($request, 'offer');
        $quantityRaw = $this->extractParam($request, 'quantity');
        $unitPrice = $this->extractParam($request, 'unitPrice');
        $annualPayment = (bool) $this->extractParam($request, 'annualPayment');
        $discountPercent = $this->extractParam($request, 'discountPercent');
        $total = $this->extractParam($request, 'total');
        $createdAt = $this->extractParam($request, 'createdAt');
        $simulate = (bool) $this->extractParam($request, 'simulate');

        $offer = $offerId ? $offerRepository->find($offerId) : null;
        $available = $unitRepository->countAvailable($offer);

        // valider quantity
        if (null === $quantityRaw || trim((string)$quantityRaw) === '') {
            $this->addFlash('error', 'Veuillez indiquer une quantité valide.');
            return $this->redirectToRoute('app_order_new');
        }

        $quantity = (int) $quantityRaw;

        // Valider min/max si l'offre définit ces valeurs
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

        // Si simulation, récupérer les unités proposées
        $simulatedUnits = [];
        if ($simulate && $quantity > 0) {
            $units = $unitRepository->findAvailable($offer, $quantity);
            $simulatedUnits = array_map(fn($u) => $u->getId(), $units);
        }

        // si unitPrice absent, le calculer
        if (null === $unitPrice) {
            $unitPrice = $calculator->calculateUnitPrice($offer, $annualPayment);
        }

        // si total absent, le recalculer
        if (null === $total) {
            $total = $calculator->calculateTotal((string)$unitPrice, $quantity, $discountPercent);
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
    public function submit(Request $request, EntityManagerInterface $em, OfferRepository $offerRepository, UnitRepository $unitRepository, PriceCalculator $calculator): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!($user instanceof Client)) {
            $this->addFlash('error', 'Vous devez être connecté en tant que client pour passer une commande.');
            return $this->redirectToRoute('app_login');
        }

        // Supporte soit des champs simples (offer, quantity, ...) soit un tableau 'order' (order[quantity], ...)
        $orderData = $request->request->get('order');
        if (is_array($orderData)) {
            $offerId = $orderData['offer'] ?? $request->request->get('offer');
            $quantity = (int) ($orderData['quantity'] ?? $request->request->get('quantity') ?? 0);
            $unitPrice = $orderData['unitPrice'] ?? $request->request->get('unitPrice');
            $annualPayment = isset($orderData['annualPayment']) ? (bool)$orderData['annualPayment'] : (bool)($request->request->get('annualPayment') ?? false);
            $discountPercent = $orderData['discountPercent'] ?? $request->request->get('discountPercent');
            $total = $orderData['total'] ?? $request->request->get('total');
            $createdAtRaw = $orderData['createdAt'] ?? $request->request->get('createdAt');
            $simulate = isset($orderData['simulate']) ? (bool)$orderData['simulate'] : (bool)($request->request->get('simulate') ?? false);
        } else {
            $offerId = $request->request->get('offer');
            $quantity = (int) $request->request->get('quantity');
            $unitPrice = $request->request->get('unitPrice');
            $annualPayment = (bool)$request->request->get('annualPayment');
            $discountPercent = $request->request->get('discountPercent');
            $total = $request->request->get('total');
            $createdAtRaw = $request->request->get('createdAt');
            $simulate = (bool)$request->request->get('simulate');
        }

        $offer = $offerId ? $offerRepository->find($offerId) : null;

        // Vérification de disponibilité
        $available = $unitRepository->countAvailable($offer);
        if ($quantity > $available) {
            $this->addFlash('error', sprintf('Il n\'y a que %d unité(s) disponible(s).', $available));
            return $this->redirectToRoute('app_order_new');
        }

        // Server-side card validation (Luhn) when not a simulation
        if (!$simulate) {
            $cardNumber = $request->request->get('card_number');
            if (!$cardNumber || !$this->isValidCardNumber($cardNumber)) {
                $this->addFlash('card_error', 'Numéro de carte invalide ou manquant.');

                // re-render confirm page with same data
                $units = $unitRepository->findAvailable($offer, $quantity);
                $simulatedUnitIds = array_map(fn($u) => $u->getId(), $units);

                return $this->render('order/confirm.html.twig', [
                    'offer' => $offer,
                    'quantity' => $quantity,
                    'unitPrice' => $unitPrice,
                    'annualPayment' => $annualPayment,
                    'discountPercent' => $discountPercent,
                    'total' => $total,
                    'createdAt' => $createdAtRaw,
                    'available' => $available,
                    'simulate' => false,
                    'simulatedUnits' => $simulatedUnitIds,
                ]);
            }
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

        // Ne pas faire confiance aux valeurs envoyées par le client : recalculer le prix unitaire et le total côté serveur
        $calculatedUnitPrice = $calculator->calculateUnitPrice($offer, $annualPayment);
        $order->setUnitPrice($calculatedUnitPrice);

        $order->setAnnualPayment($annualPayment);
        if (null !== $discountPercent) {
            $order->setDiscountPercent($discountPercent);
        }
        // calculer le total côté serveur
        $order->setTotal($calculator->calculateTotal((string)$calculatedUnitPrice, $quantity, $discountPercent));
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

    /**
     * Récupère un paramètre POST de manière robuste : gère order[...] (array), champ plat,
     * body JSON et parse_str sur le body brut.
     */
    private function extractParam(Request $request, string $key)
    {
        // 1) order[...] array
        $order = $request->request->get('order');
        if (is_array($order) && array_key_exists($key, $order)) {
            return $order[$key];
        }

        // 2) champ plat
        if ($request->request->has($key)) {
            return $request->request->get($key);
        }

        // 3) body JSON
        $content = $request->getContent();
        if ($content) {
            $json = json_decode($content, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                if (isset($json[$key])) return $json[$key];
                if (isset($json['order']) && is_array($json['order']) && isset($json['order'][$key])) return $json['order'][$key];
            }

            // 4) parse_str (form-encoded body but maybe Request->request empty)
            $parsed = [];
            parse_str($content, $parsed);
            if (isset($parsed[$key])) return $parsed[$key];
            if (isset($parsed['order']) && is_array($parsed['order']) && isset($parsed['order'][$key])) return $parsed['order'][$key];
        }

        return null;
    }
}
