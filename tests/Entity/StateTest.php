<?php

namespace App\Tests\Entity;

use App\Entity\State;
use PHPUnit\Framework\TestCase;

class StateTest extends TestCase
{
    public function testLibelleStateCanBeModified(): void
    {
        // Créer une instance de State
        $state = new State();

        // Par défaut, le libellé est null
        $this->assertNull($state->getLibelleState());

        // Définir un libellé
        $state->setLibelleState('Initial');
        $this->assertSame('Initial', $state->getLibelleState());

        // Modifier le libellé
        $state->setLibelleState('Modifié');
        $this->assertSame('Modifié', $state->getLibelleState());

        // Vérifier la conversion en string via __toString
        $this->assertSame('Modifié', (string) $state);
    }
}
