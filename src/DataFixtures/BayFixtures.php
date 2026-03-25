<?php

namespace App\DataFixtures;

use App\Entity\Bay;
use App\Entity\State;
use App\Entity\Unit;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class BayFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // créer un état réutilisable pour toutes les unités
        $state = new State();
        $state->setLibelleState('Disponible');
        $manager->persist($state);

        $stateDisable = new State();
        $stateDisable->setLibelleState('Indisponible');
        $manager->persist($stateDisable);

        $stateMaintenance = new State();
        $stateMaintenance->setLibelleState('En maintenance');
        $manager->persist($stateMaintenance);

        // Générer 30 baies par défaut (B01..B30)
        $nbBays = 30;
        $unitsPerBay = 42;

        for ($i = 1; $i <= $nbBays; $i++) {
            $bayName = sprintf('B%02d', $i);
            $bay = new Bay();
            $bay->setNameBay($bayName);
            $manager->persist($bay);

            // créer 42 unités pour chaque baie
            for ($j = 1; $j <= $unitsPerBay; $j++) {
                $unit = new Unit();
                $unit->setBay($bay);
                $unit->setState($state);
                // marquer l'unité comme libre
                $unit->setIsFree(true);

                $manager->persist($unit);
            }
        }

        $manager->flush();
    }
}
