<?php

namespace App\Service;

use App\Entity\Offer;

class PriceCalculator
{
    /**
     * Calcule le prix unitaire applicable pour une offre.
     * Ici on retourne l'offre->getPrice() par défaut. Vous pouvez adapter
     * les règles (annualPayment discount, promotions, etc.) ici.
     *
     * @param Offer|null $offer
     * @param bool $annualPayment
     * @return string prix unitaire sous forme de string DECIMAL (ex: '10.00')
     */
    public function calculateUnitPrice(?Offer $offer, bool $annualPayment = false): string
    {
        if (null === $offer) {
            return '0.00';
        }

        $price = $offer->getPrice() ?? '0.00';

        // Exemple simple: si annualPayment, appliquer une remise de 10%
        if ($annualPayment) {
            $discounted = bcmul($price, '0.90', 2);
            return $discounted;
        }

        return $price;
    }

    /**
     * Calcule le total en tenant compte de quantity et discountPercent.
     * Toutes les valeurs sont attendues en string decimales pour précision.
     *
     * @param string $unitPrice
     * @param int $quantity
     * @param string|null $discountPercent (ex: '5.00' pour 5%)
     * @return string total (DECIMAL string)
     */
    public function calculateTotal(string $unitPrice, int $quantity, ?string $discountPercent = null): string
    {
        $qty = (string)$quantity;
        $subtotal = bcmul($unitPrice, $qty, 2);

        if (null === $discountPercent || (float)$discountPercent <= 0) {
            return $subtotal;
        }

        // discountPercent est un pourcentage (0-100)
        $multiplier = bcsub('1', bcdiv($discountPercent, '100', 4), 4);
        $total = bcmul($subtotal, $multiplier, 2);

        return $total;
    }
}
