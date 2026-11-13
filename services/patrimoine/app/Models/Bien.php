<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Bien extends Model
{
    use HasUuids;

    protected $fillable = [
        'designation',
        'categorie',
        'valeur_acquisition',
        'date_acquisition',
        'etat',
        'statut',
        'latitude',
        'longitude',
        'localisation',
        'structure_id',
        'responsable_id',
        // `code_unique` n'est volontairement pas modifiable : il est calculé
        // à la création et jamais accepté en mise à jour.
    ];

    protected function casts(): array
    {
        return [
            'valeur_acquisition' => 'integer',
            'date_acquisition' => 'date',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function mouvements()
    {
        return $this->hasMany(Mouvement::class)->orderByDesc('date');
    }

    /**
     * Catégories autorisées (référentiel simple, pas de table dédiée).
     */
    public static array $categories = [
        'Matériel informatique',
        'Mobilier',
        'Immeubles',
        'Véhicules',
        'Autres',
    ];

    public static array $etats = ['bon', 'usage', 'hors_service'];
}
