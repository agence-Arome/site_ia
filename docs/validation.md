# Rapport de validation — 30 septembre 2026

## Environnement réellement utilisé

Windows, PHP portable **8.3.35**, WordPress **7.1.2**, traduction française et extension officielle SQLite Database Integration pour la base locale. Navigateur Chromium piloté par Playwright. Les outils et la base sont isolés hors du dépôt livré.

Docker n’était pas disponible dans le PATH. La configuration Compose/MariaDB est fournie, mais **n’a pas été exécutée dans cette session**. La recette sur MariaDB et sur l’hébergement cible reste un préalable à la production.

## Résultats

| Vérification | Résultat |
|---|---|
| Syntaxe des 11 fichiers PHP du thème et de l’extension | Aucune erreur |
| WordPress Coding Standards 3.4.1 / PHPCS 3.13.6 | Aucune erreur ni avertissement |
| Intégration sur WordPress réel | **39 assertions réussies** |
| Parcours navigateur | **38 vérifications réussies** |
| Vérifications HTTP supplémentaires | **10 vérifications réussies** |
| Validité des 17 contenus initiaux dans Gutenberg | Aucun bloc invalide |
| Deuxième exécution de l’installation éditoriale | 17 contenus conservés, aucun écrasement |
| Accessibilité axe 4.10.3, règles WCAG A/AA ciblées | Aucune violation sur les 5 pages examinées |
| Responsive | Aucun débordement horizontal à 320, 390, 768 et 1440 px sur l’accueil |
| JavaScript | Aucune erreur pendant la suite navigateur |

### Ce que couvrent les tests

Les six prestations, les champs invalides et trop longs, les valeurs hors liste, le nettoyage XSS, les jetons expirés/altérés/liés à un autre navigateur, le refus d’origine externe, la limitation de fréquence, le stockage privé, les doublons, les permissions anonyme/éditeur/administrateur, l’export, l’effacement, la purge, l’échec e-mail sans perte de données et sa reprise.

Dans le navigateur : formulaires réellement soumis avec et sans JavaScript, confirmation, événement unique, absence de recomptage au rechargement et au renvoi, piège anti-spam, absence de route REST des leads, menu mobile et fermeture par Échap, affichage du lead, changement de statut et rejet d’une mutation sans nonce.

Les six pages de service retournent HTTP 200, comportent des données structurées et une seule URL canonique. L’URL de confirmation seule ne permet pas de simuler un enregistrement.

L’inspection visuelle a porté sur l’accueil ordinateur/mobile et le devis mobile. Les rapports d’accessibilité et Gutenberg sont conservés dans `docs/test-results/`. Toutes les demandes fictives de la suite ont été effacées après les essais.

## Mesure locale de performance

Une navigation locale sur l’accueil, fenêtre 1440 px, sans limitation réseau ou CPU :

- TTFB : **275 ms**.
- LCP observé : **496 ms**.
- CLS observé : **0**.
- Document et ressources transférés : **152 518 octets**, cinq ressources annexes.
- Aucune origine externe chargée.

Mesure indicative unique, détail dans `performance-local.json`. Elle ne constitue pas une mesure terrain, un percentile utilisateur, un audit Lighthouse ni une garantie sur un hébergement distant. L’INP n’a pas été mesuré.

## Limites à lever avant publication

- Recette MariaDB, Docker et serveur cible ; test de sauvegarde/restauration et des tâches planifiées réelles.
- Livraison effective des e-mails : les tests simulent le résultat du transport ; aucun message réel n’a été envoyé à un prospect.
- Vérification humaine avec lecteur d’écran, parcours clavier complet et zoom ; axe seul ne certifie pas WCAG.
- Mesures de performance avec réseau mobile et données terrain après lancement.
- Finalisation du nom commercial, de l’identité, des contenus légaux, de la conservation et des droits des utilisateurs.
- Revue de sécurité de l’ensemble de l’hébergement et de ses autres extensions ; les tests ne constituent pas un pentest.

Aucun environnement de production n’a été modifié. Aucune URL publique n’a été publiée.
