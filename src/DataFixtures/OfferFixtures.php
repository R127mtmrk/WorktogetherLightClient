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
            ['price' => '10.00', 'name' => 'Offre 1', 'minUnits' => 1, 'isActive' => true],
            ['price' => '8.50', 'name' => 'Offre 2', 'minUnits' => 5, 'isActive' => true],
            ['price' => '5.00', 'name' => 'Offre 3', 'minUnits' => 10, 'isActive' => true],
            ['price' => '12.00', 'name' => 'Offre 4', 'minUnits' => null, 'isActive' => false],
        ];

        foreach ($offers as $i => $data) {
            $offer = new Offer();
            $offer->setPrice($data['price']);
            $offer->setNameOffer($data['name']);
            $offer->setMinUnits($data['minUnits']);
            $offer->setIsActive($data['isActive']);


            $manager->persist($offer);
        }

        $manager->flush();
    }
}
