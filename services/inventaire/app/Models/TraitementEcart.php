<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

/**
 * Traitement d'un écart par le comptable des matières : en ajout seul.
 */
class TraitementEcart extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $table = 'traitements_ecart';

    protected $fillable = [
        'controle_id',
        'auteur_id',
        'action',
        'commentaire',
        'date',
    ];

    protected function casts(): array
    {
        return ['date' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new RuntimeException('Un traitement d’écart est en ajout seul : il ne peut pas être modifié.'));
        static::deleting(fn () => throw new RuntimeException('Un traitement d’écart est en ajout seul : il ne peut pas être supprimé.'));
    }

    public static array $actions = [
        'regularise' => 'Écart régularisé',
        'reforme' => 'Bien réformé',
        'main_levee' => 'Main levée après vérification',
        'autre' => 'Autre décision',
    ];

    public function controle()
    {
        return $this->belongsTo(Controle::class);
    }
}
