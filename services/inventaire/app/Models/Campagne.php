<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Campagne extends Model
{
    use HasUuids;

    protected $table = 'campagnes';

    protected $fillable = ['nom', 'date_debut', 'date_fin', 'structure_id', 'statut'];

    protected function casts(): array
    {
        return [
            'date_debut' => 'date',
            'date_fin' => 'date',
        ];
    }

    public function controles()
    {
        return $this->hasMany(Controle::class);
    }
}
