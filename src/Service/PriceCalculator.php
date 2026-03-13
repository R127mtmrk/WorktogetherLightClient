<?php

namespace App\Service;

use App\Entity\Offer;

class PriceCalculator
{
    private const DEFAULT_UNIT_PRICE = '10.00'; // prix unitaire par défaut si aucune source définie

    /**
     * Calcule le prix unitaire applicable pour une offre.
     * Maintenant, l'offre contient un `discountPercent` (ex: '10.00' pour 10%).
     * Le prix unitaire est donc le prix de base diminué du pourcentage.
     *
     * @param Offer|null $offer
     * @param bool $annualPayment
     * @return string prix unitaire sous forme de string DECIMAL (ex: '9.00')
     */
    public function calculateUnitPrice(?Offer $offer, bool $annualPayment = false): string
    {
        $base = self::DEFAULT_UNIT_PRICE;

        // appliquer réduction de l'offre si présente
        if (null !== $offer && null !== $offer->getDiscountPercent()) {
            $discountPercent = $offer->getDiscountPercent();
            if ((float)$discountPercent > 0) {
                $multiplier = bcsub('1', bcdiv($discountPercent, '100', 4), 4);
                $price = bcmul($base, $multiplier, 2);
            } else {
                $price = $base;
            }
        } else {
            $price = $base;
        }

        // Exemple simple: si annualPayment, appliquer une remise de 10% supplémentaire
        if ($annualPayment) {
            $price = bcmul($price, '0.90', 2);
        }

        return $price;
    }

    /**
     * Calcule le total en tenant compte de quantity et discountPercent (supplémentaire au niveau de la commande).
     * Toutes les valeurs sont attendues en string decimales pour précision.
     *
     * @param string $unitPrice
     * @param int $quantity
     * @param string|null $discountPercent (ex: '5.00' pour 5%) — réduction appliquée sur le sous-total
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
