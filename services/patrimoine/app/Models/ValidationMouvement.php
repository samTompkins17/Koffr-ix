<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

/**
 * Décision de validation d'un mouvement : en ajout seul.
 */
class ValidationMouvement extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $table = 'validations_mouvement';

    protected $fillable = [
        'mouvement_id',
        'decideur_id',
        'decision',
        'motif',
        'date',
    ];

    protected function casts(): array
    {
        return ['date' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new RuntimeException('Une validation est en ajout seul : elle ne peut pas être modifiée.'));
        static::deleting(fn () => throw new RuntimeException('Une validation est en ajout seul : elle ne peut pas être supprimée.'));
    }

    public function mouvement()
    {
        return $this->belongsTo(Mouvement::class);
    }
}
