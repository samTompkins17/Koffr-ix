<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasUuids, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'structure_id',
        'actif',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'actif' => 'boolean',
        ];
    }

    /** Identifiant placé dans le claim `sub` du jeton. */
    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    /**
     * Claims ajoutés automatiquement par le guard : les claims métier
     * (name, role, structure_id, ministere_code) sont passés explicitement
     * par le contrôleur de connexion.
     */
    public function getJWTCustomClaims(): array
    {
        return [];
    }

    public function structure()
    {
        return $this->belongsTo(Structure::class);
    }

    /**
     * Code du ministère de rattachement : on remonte la hiérarchie
     * des structures jusqu'au ministère (structure sans parent).
     * Ce code sert à fabriquer le code patrimoine (PAT-SAN-2026-0001).
     */
    public function ministereCode(): ?string
    {
        $structure = $this->structure;
        $code = null;

        while ($structure) {
            $code = $structure->code;
            $structure = $structure->parent;
        }

        return $code;
    }
}
