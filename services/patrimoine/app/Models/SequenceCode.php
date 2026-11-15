<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SequenceCode extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $table = 'sequences_code';

    protected $fillable = ['ministere_code', 'annee', 'dernier_numero'];
}
