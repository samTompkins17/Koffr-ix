<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Les tests utilisent toujours `inventaire_test`, jamais la base de
     * démonstration. La règle est posée avant le démarrage de l'application.
     */
    protected function setUp(): void
    {
        putenv('DB_DATABASE=inventaire_test');
        $_ENV['DB_DATABASE'] = 'inventaire_test';
        $_SERVER['DB_DATABASE'] = 'inventaire_test';

        parent::setUp();
    }
}
