# Expert WP — MVP WordPress sur mesure

Thème de blocs Gutenberg, six pages services, devis conditionnel et extension de gestion des demandes. Version **0.1.0**, livrée pour préparation de préproduction, sans déploiement en production.

**Consulter sans installation :** ouvrir `index.html`, qui regroupe les 16 pages dans un fichier autonome. Les formulaires sont démonstratifs et ne transmettent rien.

**Travailler à deux :** suivre [CONTRIBUTING.md](CONTRIBUTING.md) pour installer le projet sur chaque poste, créer des branches, proposer des pull requests et régénérer l’aperçu. Le code est partagé ; les bases WordPress restent séparées.

## Contenu du projet

- `wp-content/themes/expert-wp` : présentation responsive, compositions Gutenberg, modèles de pages, articles et réalisations.
- `wp-content/plugins/expert-wp-leads` : formulaires, leads privés, administration, notifications, confidentialité, SEO essentiel.
- `tools/content.json` et `tools/seed-content.php` : contenus initiaux, installation explicite et idempotente.
- `tests` : intégration WordPress et parcours navigateur avec axe.
- `docs` : architecture, sécurité, SEO, édition, installation et déploiement.

Les mentions légales, la confidentialité et le premier article sont en **brouillon**. Aucune réalisation, certification ni preuve client n’est inventée. Le nom « Expert WP » est provisoire. Les formulaires sont bloqués en environnement `production` si aucune politique de confidentialité publiée n’est sélectionnée dans WordPress.

## Installation locale avec Docker

Prérequis : Docker Desktop avec Compose, Git. Pour les contrôles hors conteneurs : PHP 8.3+, Composer, Node 22+ et npm. Le serveur doit être installé à la racine du domaine ; les liens éditoriaux initiaux commencent par `/`.

1. Copier `.env.example` vers `.env` et renseigner deux mots de passe locaux distincts et aléatoires. Ne jamais les committer.
2. Démarrer les services :

```sh
docker compose up -d db wordpress mailpit
docker compose run --rm cli core install --url=http://localhost:8080 --title="Expert WP" --admin_user=site-admin --admin_email=admin@example.test --prompt=admin_password
docker compose run --rm cli language core install fr_FR --activate
docker compose run --rm cli plugin activate expert-wp-leads
docker compose run --rm cli theme activate expert-wp
docker compose run --rm cli eval-file /var/www/html/project-tools/seed-content.php
```

Le mot de passe administrateur est demandé interactivement. Si `WP_PORT` change, adapter l’URL d’installation. Les services sont liés à `127.0.0.1` : aucune exposition réseau publique n’est nécessaire.

- Site : `http://localhost:8080`
- Administration : `http://localhost:8080/wp-admin/`
- E-mails de test : `http://localhost:8025`

Dans **Demandes → Réglages**, saisir `notify@example.test` et activer les notifications pour les essais. Le transport Mailpit n’est utilisé que si `WP_ENVIRONMENT_TYPE=local` et `EWP_MAILPIT=true`.

Le script de contenus conserve les pages déjà présentes. Les réglages de page d’accueil, blog et permaliens ne sont initialisés qu’une fois. Il ne convient pas pour une migration automatique de contenus existants.

## Vérifications

```sh
composer install
composer lint
docker compose run --rm cli eval-file /var/www/html/project-tests/integration.php
npm install
npx playwright install chromium
npm run test:browser
```

Les tests doivent utiliser une base **locale isolée**, jamais la production. Le test navigateur exige les contenus initiaux et crée deux demandes fictives à effacer ensuite. Les tests d’intégration nettoient leurs propres demandes et leur utilisateur temporaire.

Variables du test navigateur :

- `WP_TEST_URL` : URL locale, défaut `http://127.0.0.1:8080` ; utiliser exactement le domaine configuré dans WordPress.
- `WP_TEST_USER` : identifiant administrateur du site de test.
- `WP_TEST_PASSWORD` ou `WP_TEST_PASSWORD_FILE` : secret de test hors dépôt, pour couvrir aussi l’administration et Gutenberg.
- `TEST_ARTIFACTS` : destination des rapports, défaut `test-results/` ignoré par Git.
- `PLAYWRIGHT_EXECUTABLE`, `PLAYWRIGHT_MODULE`, `AXE_PATH` : adaptations facultatives à un environnement déjà équipé.

Après une suite navigateur, supprimer les demandes `browser-fixture@example.test` et `nojs-fixture@example.test` dans l’administration. Entre deux exécutions rapprochées, attendre dix minutes ou supprimer **uniquement sur la base de test** les transients `ewp_rate_*` pour ne pas déclencher volontairement la limitation anti-spam.

La suite navigateur sans identifiants ne teste pas l’administration ni Gutenberg. Consulter `docs/validation.md` pour les résultats réellement obtenus et les limites.

## Gestion quotidienne

- **Demandes** : filtrer par statut, prestation, date ou e-mail ; consulter une demande ; changer son statut ; relancer sa notification ; l’effacer explicitement.
- **Demandes → Réglages** : notifications, destinataire, durée de conservation, SEO et identité réelle de l’entreprise.
- **Outils → Exporter/Effacer les données personnelles** : utiliser le processus de vérification d’adresse de WordPress.
- **Pages / Articles / Réalisations** : éditer les contenus avec Gutenberg.
- **Apparence → Éditeur** : navigation, en-tête, pied de page, palette et compositions.

La durée initiale de conservation est **90 jours depuis la réception, tous statuts confondus**. Les notifications sont **désactivées** tant que le destinataire n’est pas configuré. Un retour positif de `wp_mail` signifie acceptation par le transport, pas livraison garantie.

## Installation sur un hébergement existant

Installer les deux archives ZIP fournies via **Apparence → Thèmes** et **Extensions → Ajouter**, puis les activer. Importer les contenus avec WP-CLI après copie du dossier `tools`, ou créer les pages dans Gutenberg et insérer les blocs « Demande de devis » et « Confirmation de demande ». Pour le fonctionnement par défaut, garder les URL `/devis/`, `/contact/` et `/merci/`.

Ne pas remplacer le cœur WordPress. Ne pas importer une base locale sur un site ayant déjà reçu des demandes réelles. Voir `docs/deployment.md`.

## Avant une mise en production

Confirmer l’identité, les contenus légaux, le domaine, l’hébergement, le transport e-mail, la conservation, les utilisateurs habilités et le responsable des sauvegardes. Recetter la version sur MariaDB et sur le serveur cible, puis obtenir l’autorisation explicite de déployer.

Licence du code : GPL-2.0-or-later. Les licences des dépendances restent celles de leurs auteurs.
