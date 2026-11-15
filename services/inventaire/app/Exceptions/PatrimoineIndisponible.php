<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Levée quand le service patrimoine ne répond pas dans le délai imparti
 * ou renvoie une erreur : le contrôle par scan est alors dégradé (503).
 */
class PatrimoineIndisponible extends RuntimeException
{
    protected $message = 'Le service patrimoine est momentanément indisponible.';
}
