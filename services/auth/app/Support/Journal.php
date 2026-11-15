<?php

namespace App\Support;

use App\Models\JournalAudit;

/**
 * Petit utilitaire pour écrire dans le journal d'audit.
 * Chaque service possède son propre journal : ils ne sont pas fusionnés.
 */
class Journal
{
    public static function ecrire(?string $utilisateurId, string $action, ?string $objet = null, ?string $detail = null): void
    {
        JournalAudit::create([
            'utilisateur_id' => $utilisateurId,
            'action' => $action,
            'objet' => $objet,
            'detail' => $detail,
            'date' => now(),
        ]);
    }
}
