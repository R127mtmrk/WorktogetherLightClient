<?php

namespace App\DataFixtures;

use App\Entity\Offer;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class OfferFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $offers = [
            ['discountPercent' => '10.00', 'name' => 'Offre 1', 'minUnits' => 1, 'isActive' => true],
            ['discountPercent' => '15.00', 'name' => 'Offre 2', 'minUnits' => 5, 'isActive' => true],
            ['discountPercent' => '20.00', 'name' => 'Offre 3', 'minUnits' => 10, 'isActive' => true],
            ['discountPercent' => null, 'name' => 'Offre 4', 'minUnits' => 10, 'isActive' => false],
        ];

        foreach ($offers as $i => $data) {
            $offer = new Offer();
            $offer->setDiscountPercent($data['discountPercent']);
            $offer->setNameOffer($data['name']);
            $offer->setMinUnits($data['minUnits']);
            $offer->setIsActive($data['isActive']);

            $manager->persist($offer);
        }

        $manager->flush();
    }
}
