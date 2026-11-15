<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Structure extends Model
{
    use HasUuids;

    protected $fillable = ['nom', 'code', 'type', 'region', 'parent_id'];

    public function parent()
    {
        return $this->belongsTo(Structure::class, 'parent_id');
    }

    public function enfants()
    {
        return $this->hasMany(Structure::class, 'parent_id');
    }
}
