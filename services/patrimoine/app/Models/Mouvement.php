<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

/**
 * Mouvement de patrimoine : affectation, transfert, maintenance ou sortie.
 * Table en ajout seul : aucune modification, aucune suppression.
 */
class Mouvement extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'bien_id',
        'type',
        'date',
        'structure_origine_id',
        'structure_destination_id',
        'motif',
        'statut_validation',
        'auteur_id',
    ];

    protected function casts(): array
    {
        return ['date' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new RuntimeException('Un mouvement est en ajout seul : il ne peut pas être modifié.'));
        static::deleting(fn () => throw new RuntimeException('Un mouvement est en ajout seul : il ne peut pas être supprimé.'));
    }

    public function bien()
    {
        return $this->belongsTo(Bien::class);
    }

    /**
     * Décision de validation (une seule par mouvement).
     */
    public function validation()
    {
        return $this->hasOne(ValidationMouvement::class);
    }

    public static array $types = ['affectation', 'transfert', 'maintenance', 'sortie'];
}
