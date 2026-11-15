<?php

namespace Tests\Support;

use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

/**
 * Représentation minimale d'un utilisateur, uniquement pour signer un jeton
 * dans les tests : inventaire ne stocke pas les comptes (service auth).
 */
class UtilisateurJeton implements JWTSubject
{
    public function __construct(
        public string $id = '00000000-0000-4000-8000-000000000999',
        public string $nom = 'Agent de test',
        public string $role = 'comptable_matieres',
        public ?string $structureId = '00000000-0000-4000-8000-000000000004',
        public ?string $ministereCode = 'SAN',
    ) {}

    public function getJWTIdentifier(): mixed
    {
        return $this->id;
    }

    public function getJWTCustomClaims(): array
    {
        return [
            'name' => $this->nom,
            'role' => $this->role,
            'structure_id' => $this->structureId,
            'ministere_code' => $this->ministereCode,
        ];
    }
}
