<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

/**
 * Pièce jointe privée d'un bien : métadonnées en ajout seul.
 */
class Document extends Model
{
    use HasUuids;

    protected $fillable = [
        'bien_id',
        'type',
        'nom_original',
        'chemin',
        'mime_type',
        'taille',
        'auteur_id',
    ];

    // Le chemin de stockage ne doit jamais apparaître dans les réponses JSON.
    protected $hidden = ['chemin'];

    public static array $types = ['facture', 'acte', 'titre', 'photo', 'autre'];

    protected function casts(): array
    {
        return ['taille' => 'integer'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new RuntimeException('Un document est en ajout seul : il ne peut pas être modifié.'));
        static::deleting(fn () => throw new RuntimeException('Un document est en ajout seul : il ne peut pas être supprimé.'));
    }

    public function bien()
    {
        return $this->belongsTo(Bien::class);
    }
}
