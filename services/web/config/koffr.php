<?php

/**
 * Réglages propres à l'interface KOFFR-IX.
 */
return [

    // Libellés des ministères, déduits du code présent dans le code patrimoine.
    // Le service patrimoine ne stocke que des UUID : la page publique (sans
    // connexion) ne peut donc pas résoudre le nom via le service auth.
    'ministeres' => [
        'SAN' => 'Ministère de la Santé',
        'FIN' => 'Ministère des Finances et du Budget',
        'EDU' => 'Ministère de l’Éducation',
    ],

    // Libellés des rôles affichés dans l'en-tête.
    'roles' => [
        'admin' => 'Administrateur',
        'comptable_matieres' => 'Comptable des matières',
        'agent_patrimoine' => 'Agent du patrimoine',
        'responsable_service' => 'Responsable de service',
        'controleur' => 'Contrôleur',
    ],

    'categories' => [
        'Matériel informatique',
        'Mobilier',
        'Immeubles',
        'Véhicules',
        'Autres',
    ],

    'etats' => [
        'bon' => ['libelle' => 'Bon', 'icone' => '✓'],
        'usage' => ['libelle' => 'Usagé', 'icone' => '▲'],
        'hors_service' => ['libelle' => 'Hors service', 'icone' => '●'],
        'introuvable' => ['libelle' => 'Introuvable', 'icone' => '✕'],
    ],
];
