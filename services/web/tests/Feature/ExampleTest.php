<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Sans jeton en session, la page d'accueil renvoie vers la connexion.
     */
    public function test_accueil_redirige_vers_la_connexion(): void
    {
        $this->get('/')->assertRedirect('/connexion');
    }

    public function test_la_page_de_connexion_saffiche(): void
    {
        $this->get('/connexion')->assertOk()->assertSee('Connexion');
    }
}
