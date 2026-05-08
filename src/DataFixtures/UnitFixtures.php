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
        $stateDisponible = new State();
        $stateDisponible->setLibelleState('Disponible');
        $manager->persist($stateDisponible);
        $manager->flush();
        $this->addReference('state-disponible', $stateDisponible);

        $nbBays = 30;
        $unitsPerBay = 42;

        for ($i = 1; $i <= $nbBays; $i++) {
            $bay = new Bay();
            $bay->setNameBay(sprintf('B#%02d', $i));
            $manager->persist($bay);
            $manager->flush();
            $this->addReference(sprintf('bay-%02d', $i), $bay);

            for ($j = 1; $j <= $unitsPerBay; $j++) {
                $unit = new Unit();
                $unit->setBay($bay);
                $unit->setState($stateDisponible);
                $unit->setIsFree(true);
                $manager->persist($unit);
            }

            $manager->flush();
        }
    }
}
