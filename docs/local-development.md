# Environnement local

## Parcours reproductible livré

Le README décrit les commandes Docker Compose. Le cœur WordPress réside dans un volume ; seuls le thème, l’extension, les outils et les tests sont montés depuis le dépôt. La base est dans un volume distinct. Les secrets viennent du fichier `.env` ignoré.

Ne pas exécuter `docker compose down -v` sans sauvegarde : cette commande supprime les volumes et leurs données. Un simple `docker compose stop` suffit pour interrompre le site local.

Le test d’intégration s’exécute avec WP-CLI et de vraies API WordPress. Il s’agit d’une suite d’assertions d’intégration, pas de mocks prétendant reproduire WordPress. PHPUnit n’a pas été ajouté au MVP afin de ne pas multiplier les infrastructures de test pour ces parcours.

## Environnement de démonstration de cette livraison

Comme PHP et Docker n’étaient pas disponibles dans le PATH, les essais ont utilisé PHP portable et WordPress avec SQLite dans le dossier de travail temporaire de cette conversation. Aucun outil n’a été installé globalement. PHP a été vérifié par son empreinte SHA-256 officielle.

L’aperçu est servi uniquement sur `127.0.0.1:8097`. Le lanceur `Lancer-apercu-local.ps1`, fourni à côté du projet, permet de le redémarrer tant que ce dossier de travail et son runtime existent. Il n’est pas destiné à un serveur public et n’est pas inclus dans les archives installables du thème ou de l’extension.

Les identifiants de démonstration ne sont pas dans Git ni dans les archives. Un nouveau site doit créer ses propres identifiants pendant l’installation. Aucune donnée client réelle n’est présente dans l’environnement de démonstration.

## Rejouer les tests navigateur

Sur la base locale isolée, avant une nouvelle exécution rapprochée :

```sh
docker compose run --rm cli eval-file /var/www/html/project-tests/reset-browser.php
npm run test:browser
node tests/http-smoke.cjs
docker compose run --rm cli eval-file /var/www/html/project-tests/reset-browser.php
```

Le nettoyage supprime uniquement les deux adresses fictives réservées aux tests et les compteurs de limitation locaux. Les scripts refusent les cibles non locales. Définir `WP_TEST_URL` avec le même hôte que les réglages WordPress ; ne pas mélanger `localhost` et `127.0.0.1` pendant un parcours.
