<?php

namespace App\Support;

use App\Models\SequenceCode;
use Illuminate\Support\Facades\DB;

/**
 * Fabrique le code patrimoine : PAT-{ministere}-{année}-{numéro sur 4 chiffres}.
 *
 * Le compteur est incrémenté dans une transaction avec un verrou de ligne
 * (lockForUpdate), ce qui évite deux numéros identiques en cas d'appels
 * simultanés.
 */
class GenerateurCode
{
    public static function suivant(string $ministereCode, ?int $annee = null): string
    {
        $annee ??= (int) now()->year;

        return DB::transaction(function () use ($ministereCode, $annee) {
            // Crée la ligne de compteur si elle n'existe pas encore (idempotent).
            SequenceCode::insertOrIgnore([
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'ministere_code' => $ministereCode,
                'annee' => $annee,
                'dernier_numero' => 0,
            ]);

            // Verrouille la ligne le temps de l'incrément.
            $sequence = SequenceCode::where('ministere_code', $ministereCode)
                ->where('annee', $annee)
                ->lockForUpdate()
                ->firstOrFail();

            $sequence->dernier_numero++;
            $sequence->save();

            return sprintf('PAT-%s-%d-%04d', $ministereCode, $annee, $sequence->dernier_numero);
        });
    }
}
