<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

/**
 * Constat d'inventaire : table en ajout seul (aucune modification, aucune suppression).
 */
class Controle extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'campagne_id',
        'bien_id',
        'etat_fiche',
        'etat_constate',
        'latitude',
        'longitude',
        'photo_chemin',
        'ecart_detecte',
        'agent_id',
        'date',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'datetime',
            'ecart_detecte' => 'boolean',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new RuntimeException('Un contrôle est en ajout seul : il ne peut pas être modifié.'));
        static::deleting(fn () => throw new RuntimeException('Un contrôle est en ajout seul : il ne peut pas être supprimé.'));
    }

    public static array $etatsConstates = ['bon', 'usage', 'hors_service', 'introuvable'];
}
