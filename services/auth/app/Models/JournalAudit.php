<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

/**
 * Table en ajout seul : on interdit toute modification et toute suppression,
 * même si un développeur essaie de le faire depuis le code.
 */
class JournalAudit extends Model
{
    use HasUuids;

    protected $table = 'journal_audit';

    public $timestamps = false;

    protected $fillable = ['utilisateur_id', 'action', 'objet', 'detail', 'date'];

    protected function casts(): array
    {
        return ['date' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new RuntimeException('Le journal d’audit est en ajout seul.'));
        static::deleting(fn () => throw new RuntimeException('Le journal d’audit est en ajout seul.'));
    }
}
