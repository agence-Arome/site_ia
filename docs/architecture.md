# Architecture

## Frontières

Le thème n’accède pas aux leads. Il expose un design system `theme.json`, des modèles de blocs et des compositions. Aucun constructeur de pages, framework frontal ou police distante n’est nécessaire.

L’extension reste active lors d’un changement de thème. Elle enregistre les types `ewp_lead` (strictement privé) et `ewp_case` (éditorial public), les blocs dynamiques, les outils de confidentialité et le SEO essentiel. Les réglages utilisent la Settings API ; les métadonnées SEO sont modifiées avec capacité `edit_post` et nonce.

## Circuit d’une demande

1. Le formulaire est rendu côté serveur, avec un jeton HMAC lié à un cookie de session anonyme aléatoire. Aucun identifiant personnel dans le cookie.
2. Un petit script actualise les questions et rafraîchit le jeton via une route REST publique en `no-store`. Le formulaire reste utilisable sans JavaScript.
3. Le point d’entrée `admin-post.php?action=ewp_submit` accepte uniquement POST, vérifie le jeton, sa durée de vie, le champ piège et la fréquence des tentatives.
4. La validation serveur applique formats, longueurs et listes de valeurs. Les champs supplémentaires inconnus sont ignorés.
5. Un verrou atomique via `add_option` rend la même soumission idempotente. Les données sont enregistrées par les API WordPress et non par SQL construit.
6. Une notification minimale est planifiée, puis une redirection 303 mène à `/merci/`. Un reçu temporaire lié à la session permet un événement de conversion unique.

La fonctionnalité publique REST est uniquement une émission de jeton : aucun endpoint public ne retourne un lead. Les métadonnées commerciales ne sont pas enregistrées avec `show_in_rest`.

## Modèle de données

`ewp_lead` : publication privée, titre technique aléatoire sans coordonnées ; données en `_ewp_data`, e-mail en `_ewp_email` pour les demandes d’export/effacement ; service et statut séparés pour les filtres.

Historique : date UTC, identifiant de l’utilisateur et statut, limité à 100 changements. Le contenu personnel n’est pas répliqué dans cet historique. État d’envoi et nombre de tentatives séparés. Aucune IP brute ni mot de passe n’est enregistré dans le lead.

Ce stockage convient au MVP et à un volume modéré. Les filtres par métadonnées peuvent devenir coûteux à gros volume ; mesurer avant de migrer vers une table dédiée indexée. L’architecture ne prétend pas être un CRM complet.

## Choix de version

WordPress testé : 7.1.2 ; PHP testé : 8.3.35. Compatibilité déclarée WordPress ≥ 6.8, non équivalente à une matrice complète de versions testées. Composer verrouille les outils PHP. Les dépendances navigateur sont fixées dans `package.json`.

La configuration Docker cible WordPress 7.1.2/PHP 8.3 et MariaDB 11.4 ; les tags de branche PHP/MariaDB reçoivent leurs correctifs. Pour un déploiement reproductible, enregistrer les digests des images validées en préproduction.

## Arbitrages du MVP

- Pas de pièces jointes, paiement, espace client, CRM ou traceur tiers.
- Une question de qualification spécifique à chaque service, plus contexte, budget et délai.
- Réception de contact simple par le même moteur de leads.
- Pas d’accusé de réception automatique au prospect, pour éviter une fonction de rebond exploitable par des bots.
- Paramètres d’envoi : destinataire et activation. Le message interne est volontairement minimal et fixe.
- SEO essentiel optionnel. Détection de Yoast, Rank Math et AIOSEO pour éviter les doublons ; pour un autre outil, désactiver ce module explicitement.
- Compositions articles, FAQ et études de cas ; aucun bloc FAQ structuré ni résultat enrichi promis.

## Évolutions possibles

Après observation des demandes réelles : questions de qualification supplémentaires, gestion d’attribution sans données personnelles, intégration CRM, file de notifications plus robuste et stockage spécialisé. Ces évolutions ne sont pas activées implicitement.
