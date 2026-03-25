<?php

namespace App\DataFixtures;

use App\Entity\Bay;
use App\Entity\State;
use App\Entity\Unit;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class UnitFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // créer un état 'Disponible' si nécessaire
        $stateDisponible = new State();
        $stateDisponible->setLibelleState('Disponible');
        $manager->persist($stateDisponible);

        // Créer 30 baies, chacune avec 42 unités libres
        $nbBays = 30;
        $unitsPerBay = 42;

        for ($i = 1; $i <= $nbBays; $i++) {
            $bay = new Bay();
            $bay->setNameBay(sprintf('Baie #%d', $i));
            $manager->persist($bay);

            for ($j = 1; $j <= $unitsPerBay; $j++) {
                $unit = new Unit();
                $unit->setBay($bay);
                $unit->setState($stateDisponible);
                $unit->setIsFree(true);

                // Persist unit
                $manager->persist($unit);
            }
        }

        $manager->flush();
    }
}
