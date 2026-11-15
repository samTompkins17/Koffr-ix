<?php

namespace App\Support;

use App\Models\JournalAudit;

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
