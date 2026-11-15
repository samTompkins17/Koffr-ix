<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Les tests utilisent toujours `patrimoine_test`, jamais la base de
     * démonstration. La règle est posée avant le démarrage de l'application.
     */
    protected function setUp(): void
    {
        putenv('DB_DATABASE=patrimoine_test');
        $_ENV['DB_DATABASE'] = 'patrimoine_test';
        $_SERVER['DB_DATABASE'] = 'patrimoine_test';

        parent::setUp();
    }
}
