# Préproduction, production et retour arrière

## Environnement cible

WordPress avec PHP 8.3 compatible, MariaDB 11.4 ou moteur compatible officiellement pris en charge, HTTPS, WP-CLI conseillé, transport e-mail et tâche planifiée disponibles. Confirmer les versions effectivement proposées par l’hébergeur avant installation.

Le Compose fourni est une configuration de développement, pas une recette d’hébergement public. Ne pas exposer Mailpit ni les identifiants locaux en production. Le runtime SQLite utilisé lors des essais est exclu des archives et n’est pas la base prévue pour la production.

## Construire la livraison

1. `composer install` puis `composer lint` ; exécuter les tests sur une base isolée.
2. Créer une archive ZIP dont la racine est `expert-wp/` et une autre `expert-wp-leads/` à partir des deux dossiers correspondants.
3. N’inclure ni `vendor`, `node_modules`, `.env`, bases, comptes locaux ou rapports contenant des données personnelles.
4. Conserver la version, l’empreinte SHA-256 des archives et le rapport de tests avec la livraison.

## Installer en préproduction

1. Sauvegarder fichiers et base de l’environnement existant ; tester la restauration sur une copie isolée.
2. Protéger la préproduction par authentification HTTP et définir `WP_ENVIRONMENT_TYPE=staging`.
3. Installer/activer thème et extension. Exécuter le script de contenus seulement après sauvegarde et vérification du périmètre. Il ne remplace pas les pages existantes.
4. Définir domaine, langue, fuseau horaire, permaliens, accueil et blog. Contrôler les liens racine si WordPress n’est pas à la racine du domaine.
5. Compléter les mentions légales et la confidentialité ; contrôler le destinataire et le transport des notifications.
6. Exclure `/devis/`, `/contact/`, `/merci/`, toute page contenant le formulaire, `/wp-admin/admin-post.php` et `/wp-json/expert-wp/v1/token` de tout cache de page/CDN. Ne jamais mettre en cache une réponse POST.
7. Exécuter WP-Cron régulièrement via la tâche de l’hébergeur (`wp cron event run --due-now`, par exemple chaque minute), puis désactiver le déclenchement par les visites seulement lorsque cette tâche est confirmée opérationnelle.
8. Recetter les six prestations, le contact sans JavaScript, les erreurs, les droits, les e-mails, la purge, les exports et l’édition des blocs.
9. Mesurer les performances dans les conditions du serveur cible ; vérifier le clavier, le zoom et un lecteur d’écran. Faire valider le contenu par son propriétaire.

## Autorisation de production

Aucune mise en production n’est automatique. Obtenir une autorisation explicite sur la version testée et convenir d’une fenêtre d’intervention. Valider au préalable identité, données personnelles, SMTP, sauvegardes, droits, domaine et contenus publics.

## Bascule

Sauvegarder l’état courant. Déployer le code validé, sans écraser les nouveaux leads. Pour une refonte, appliquer le plan de redirections convenu. Définir `WP_ENVIRONMENT_TYPE=production`, vérifier l’indexabilité voulue, puis effectuer un contrôle immédiat : accueil, six services, formulaire, confirmation, administration, e-mail et tâches planifiées.

La première version ne crée aucune table métier spécifique. Les futures modifications de schéma devront utiliser une version de migration, être idempotentes et sauvegardées. Ne pas improviser une migration descendante en production.

## Retour arrière

Conserver les archives de code de la version précédente. En cas d’échec, restaurer d’abord le code compatible et contrôler les données. Ne pas restaurer aveuglément une ancienne base : cela ferait perdre les demandes reçues depuis la sauvegarde. Si une restauration de base s’impose, geler les écritures, sauvegarder les leads récents et préparer une réconciliation contrôlée.

## Maintenance

Surveiller l’échec des notifications, l’exécution de WP-Cron, les sauvegardes et l’espace disque. Tester les mises à jour WordPress/PHP/extensions en préproduction. Ne pas publier de données de diagnostic contenant des coordonnées dans des tickets ou journaux partagés.
