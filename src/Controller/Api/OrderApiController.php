<?php

namespace App\Controller\Api;

use App\Repository\OfferRepository;
use App\Repository\UnitRepository;
use App\Service\PriceCalculator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/order')]
final class OrderApiController extends AbstractController
{
    #[Route('/quote', name: 'api_order_quote', methods: ['GET','POST'])]
    public function quote(Request $request, OfferRepository $offerRepository, UnitRepository $unitRepository, PriceCalculator $calculator): JsonResponse
    {
        $offerId = $request->query->get('offer') ?? $request->request->get('offer');
        $quantity = $request->query->get('quantity') ?? $request->request->get('quantity');
        $annual = $request->query->get('annualPayment') ?? $request->request->get('annualPayment');
        $discountPercent = $request->query->get('discountPercent') ?? $request->request->get('discountPercent');

        $quantity = (int) $quantity;
        $annual = filter_var($annual, FILTER_VALIDATE_BOOLEAN);
        $discountPercent = $discountPercent !== null ? (string)$discountPercent : null;

        $offer = null;
        if ($offerId) {
            $offer = $offerRepository->find((int)$offerId);
        }

        $unitPrice = $calculator->calculateUnitPrice($offer, $annual);
        $total = $calculator->calculateTotal($unitPrice, max(0, $quantity), $discountPercent);

        $available = $unitRepository->countAvailable($offer);

        // get simulated unit ids if requested (limit by quantity)
        $simulatedUnits = [];
        if ($quantity > 0) {
            $units = $unitRepository->findAvailable($offer, $quantity);
            $simulatedUnits = array_map(fn($u) => $u->getId(), $units);
        }

        return $this->json([
            'success' => true,
            'data' => [
                'unitPrice' => $unitPrice,
                'total' => $total,
                'available' => $available,
                'simulatedUnits' => $simulatedUnits,
            ],
        ]);
    }
}
