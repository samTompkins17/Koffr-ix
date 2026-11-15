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

-- Le rôle dédié doit pouvoir créer ses tables dans le schéma public de SA
-- base, et hériter des tables déjà créées par le superutilisateur koffr.
\connect auth_db
GRANT ALL ON SCHEMA public TO koffr_auth;
GRANT ALL ON ALL TABLES IN SCHEMA public TO koffr_auth;
GRANT ALL ON ALL SEQUENCES IN SCHEMA public TO koffr_auth;
ALTER DEFAULT PRIVILEGES FOR ROLE koffr IN SCHEMA public GRANT ALL ON TABLES TO koffr_auth;
ALTER DEFAULT PRIVILEGES FOR ROLE koffr IN SCHEMA public GRANT ALL ON SEQUENCES TO koffr_auth;

\connect patrimoine_db
GRANT ALL ON SCHEMA public TO koffr_patrimoine;
GRANT ALL ON ALL TABLES IN SCHEMA public TO koffr_patrimoine;
GRANT ALL ON ALL SEQUENCES IN SCHEMA public TO koffr_patrimoine;
ALTER DEFAULT PRIVILEGES FOR ROLE koffr IN SCHEMA public GRANT ALL ON TABLES TO koffr_patrimoine;
ALTER DEFAULT PRIVILEGES FOR ROLE koffr IN SCHEMA public GRANT ALL ON SEQUENCES TO koffr_patrimoine;

\connect inventaire_db
GRANT ALL ON SCHEMA public TO koffr_inventaire;
GRANT ALL ON ALL TABLES IN SCHEMA public TO koffr_inventaire;
GRANT ALL ON ALL SEQUENCES IN SCHEMA public TO koffr_inventaire;
ALTER DEFAULT PRIVILEGES FOR ROLE koffr IN SCHEMA public GRANT ALL ON TABLES TO koffr_inventaire;
ALTER DEFAULT PRIVILEGES FOR ROLE koffr IN SCHEMA public GRANT ALL ON SEQUENCES TO koffr_inventaire;

\connect auth_test
GRANT ALL ON SCHEMA public TO koffr_auth;
GRANT ALL ON ALL TABLES IN SCHEMA public TO koffr_auth;
GRANT ALL ON ALL SEQUENCES IN SCHEMA public TO koffr_auth;
ALTER DEFAULT PRIVILEGES FOR ROLE koffr IN SCHEMA public GRANT ALL ON TABLES TO koffr_auth;
ALTER DEFAULT PRIVILEGES FOR ROLE koffr IN SCHEMA public GRANT ALL ON SEQUENCES TO koffr_auth;

\connect patrimoine_test
GRANT ALL ON SCHEMA public TO koffr_patrimoine;
GRANT ALL ON ALL TABLES IN SCHEMA public TO koffr_patrimoine;
GRANT ALL ON ALL SEQUENCES IN SCHEMA public TO koffr_patrimoine;
ALTER DEFAULT PRIVILEGES FOR ROLE koffr IN SCHEMA public GRANT ALL ON TABLES TO koffr_patrimoine;
ALTER DEFAULT PRIVILEGES FOR ROLE koffr IN SCHEMA public GRANT ALL ON SEQUENCES TO koffr_patrimoine;

\connect inventaire_test
GRANT ALL ON SCHEMA public TO koffr_inventaire;
GRANT ALL ON ALL TABLES IN SCHEMA public TO koffr_inventaire;
GRANT ALL ON ALL SEQUENCES IN SCHEMA public TO koffr_inventaire;
ALTER DEFAULT PRIVILEGES FOR ROLE koffr IN SCHEMA public GRANT ALL ON TABLES TO koffr_inventaire;
ALTER DEFAULT PRIVILEGES FOR ROLE koffr IN SCHEMA public GRANT ALL ON SEQUENCES TO koffr_inventaire;
