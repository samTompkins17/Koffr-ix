<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Les tests utilisent toujours la base dédiée `auth_test`, jamais la base
     * de démonstration. On l'impose avant le démarrage de l'application :
     * PHPUnit et Docker définissent tous deux DB_DATABASE, d'où cette règle
     * explicite et prioritaire.
     */
    protected function setUp(): void
    {
        putenv('DB_DATABASE=auth_test');
        $_ENV['DB_DATABASE'] = 'auth_test';
        $_SERVER['DB_DATABASE'] = 'auth_test';

        parent::setUp();
    }
}
