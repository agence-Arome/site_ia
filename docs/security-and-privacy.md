# Sécurité et données

## Accès

La capacité `manage_ewp_leads` est ajoutée uniquement au rôle administrateur à l’activation. Elle peut être attribuée explicitement à un rôle commercial dédié après validation. Les éditeurs ne la reçoivent pas. Les réglages nécessitent `manage_options`.

Chaque action d’administration contrôle la capacité, le type de publication et un nonce lié à la demande. Les statuts sont issus d’une liste fermée. Les champs personnels sont affichés avec échappement HTML ; les URL métier renseignées ne sont pas récupérées par le serveur.

## Frontière publique

Un nonce WordPress partagé par les visiteurs anonymes ne suffit pas. Le formulaire utilise un jeton signé, lié à un cookie aléatoire de navigateur, valable deux heures. Cookie HttpOnly, SameSite=Lax, Secure en HTTPS. Les pages de formulaire et confirmation doivent être exclues du cache serveur/CDN ; l’en-tête `no-store` ne corrige pas un cache mal configuré qui intercepte la requête avant PHP.

Limitation : huit tentatives par fenêtre de dix minutes et adresse réseau dérivée avec HMAC, sans stockage de l’IP brute. Aucun en-tête `X-Forwarded-For` n’est cru par défaut. Le stockage transitoire n’est pas un compteur distribué atomique : ajouter une limitation au proxy/WAF si l’exposition le nécessite. Un réseau partagé peut atteindre la limite ; adapter après observation. La protection ne garantit pas l’arrêt de tous les bots.

Le champ piège ne peut pas être atteint au clavier. La validation vérifie les formats, les tailles et les réponses autorisées. Les verrous d’idempotence atomiques évitent les doublons d’un même jeton ; ils ne fusionnent pas arbitrairement deux demandes légitimes similaires.

## Notifications et tâches planifiées

Le mail contient une référence et un lien vers l’administration. Aucun contenu de projet ni donnée de contact n’y est recopié. Trois tentatives au maximum, espacées de cinq puis dix minutes après échec. Une relance manuelle remet le compteur à zéro. L’état « accepté par le transport » ne signifie pas « livré ».

Planifier une véritable exécution périodique de WP-Cron chez l’hébergeur : les envois, purges et nettoyages de verrous en dépendent. Avec WP-Cron dépendant des visites, un site peu fréquenté peut subir des retards. Une demande reste stockée si une notification échoue.

## Conservation

Valeur initiale : 90 jours depuis la réception, quel que soit le statut. La purge s’exécute quotidiennement par lots de 100. Ce paramètre est une hypothèse technique à valider, pas une durée juridique universelle.

Les outils de confidentialité WordPress exportent et effacent les leads associés à une adresse après le processus de vérification WordPress. L’administration permet aussi l’effacement unitaire explicite. Ne pas traiter une demande de droit sur la seule base d’un e-mail non vérifié.

La désactivation et la désinstallation préservent les données commerciales. Avant suppression définitive de l’extension, traiter les données via les outils prévus et vérifier les copies de sauvegarde. Les tâches obsolètes n’envoient rien lorsque leurs callbacks ne sont plus chargés.

Les sauvegardes, logs du serveur et services e-mail ont leur propre politique de conservation. Le code de l’extension ne peut pas effacer une sauvegarde externe ni empêcher l’hébergeur de journaliser une IP.

## Secrets et infrastructure

Ne pas versionner `.env`, `wp-config.php`, exports SQL, secrets SMTP ou données de test contenant de vraies personnes. Utiliser TLS, comptes individuels, sauvegardes chiffrées et accès au moindre privilège. Prévoir l’authentification multifacteur de l’administration avec un outil adapté à l’hébergement.

Le serveur ne doit pas afficher les erreurs PHP au public. Définir `DISALLOW_FILE_EDIT=true` ; réserver l’installation des mises à jour aux personnes autorisées. La politique CSP et les autres en-têtes de sécurité seront adaptés sur le serveur cible après inventaire des ressources WordPress.

## Avant collecte réelle

Renseigner responsable, contact, base juridique, destinataires, conservation, droits et prestataires dans la politique de confidentialité. Les brouillons livrés ne sont pas des documents juridiques finalisés. Le formulaire est bloqué en environnement production sans page de confidentialité publiée et sélectionnée.
