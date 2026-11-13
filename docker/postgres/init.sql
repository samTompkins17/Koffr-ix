-- Bases : une par service (jamais partagées).
CREATE DATABASE auth_db;
CREATE DATABASE patrimoine_db;
CREATE DATABASE inventaire_db;

-- Bases dédiées aux tests PHPUnit (jamais utilisées par la démonstration).
CREATE DATABASE auth_test;
CREATE DATABASE patrimoine_test;
CREATE DATABASE inventaire_test;

-- Rôles dédiés : chaque service ne peut lire/écrire que sa propre base,
-- même si un service était compromis (voir docs/DECISIONS.md).
CREATE ROLE koffr_auth LOGIN PASSWORD 'koffr_auth';
CREATE ROLE koffr_patrimoine LOGIN PASSWORD 'koffr_patrimoine';
CREATE ROLE koffr_inventaire LOGIN PASSWORD 'koffr_inventaire';

GRANT ALL PRIVILEGES ON DATABASE auth_db TO koffr_auth;
GRANT ALL PRIVILEGES ON DATABASE patrimoine_db TO koffr_patrimoine;
GRANT ALL PRIVILEGES ON DATABASE inventaire_db TO koffr_inventaire;

GRANT ALL PRIVILEGES ON DATABASE auth_test TO koffr_auth;
GRANT ALL PRIVILEGES ON DATABASE patrimoine_test TO koffr_patrimoine;
GRANT ALL PRIVILEGES ON DATABASE inventaire_test TO koffr_inventaire;
