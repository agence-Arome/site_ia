# SEO et acquisition

## Architecture éditoriale

Six pages sous `/services/` correspondent à six intentions distinctes : création, refonte, dépannage, audit/sécurité, maintenance et développement spécifique. Elles sont reliées à la page services, au devis préselectionné et à une prestation complémentaire. Aucun ensemble de pages locales dupliquées n’est généré.

Les pages expliquent le besoin, le périmètre, la méthode et les limites. Les montants du formulaire sont des fourchettes de qualification, pas des tarifs ni des promesses commerciales.

Le premier article est un brouillon à relire. La bibliothèque de compositions permet de rédiger d’autres articles et FAQ. Les études de cas doivent décrire des interventions réelles et des résultats documentés avec autorisation du client.

## Technique

Le cœur WordPress gère les URL canoniques singulières et le sitemap natif `/wp-sitemap.xml`. L’extension ajoute un titre SEO facultatif, une description, Open Graph et un JSON-LD WebPage ou Article. Une Organization n’est décrite que si son nom réel est renseigné. Pas d’avis, note, prix, adresse ni certification fictifs.

Le thème utilise un seul H1 de contenu. Les liens sont compréhensibles et les services sont accessibles via la navigation. Les titres et descriptions se modifient dans le panneau « SEO essentiel » de chaque contenu.

La confirmation est `noindex` et exclue du sitemap. Les leads sont un type privé non indexable. La préproduction reçoit `noindex`, mais doit aussi être protégée par une authentification HTTP : robots n’est pas un contrôle d’accès.

Avec une extension SEO dédiée, désactiver « SEO essentiel » et reprendre les champs si nécessaire. Ne pas laisser deux producteurs de métadonnées en concurrence. Les types de données structurées sont limités au contenu réellement affiché.

## Mise en production

Configurer `WP_ENVIRONMENT_TYPE=production`, contrôler le réglage « Visibilité pour les moteurs de recherche », vérifier robots/canonical/sitemap sur le domaine final, éliminer les liens provisoires et définir un plan de redirections en cas de refonte. Soumettre le sitemap à Search Console avec le compte du propriétaire après son autorisation.

## Conversions

Les leads réellement enregistrés constituent le décompte opérationnel. L’événement navigateur `expertwp:lead-recorded` est déclenché après une confirmation liée à la session ; il ne contient aucune coordonnée et n’envoie rien à un tiers. Le rechargement ou le renvoi du même formulaire ne redéclenche pas l’événement.

Cette instrumentation n’est pas un tableau de bord d’audience : trafic organique, taux de conversion, attribution et coût d’acquisition nécessitent des sources et une configuration supplémentaires. Choisir l’outil, les règles de consentement et la rétention avant toute connexion. Les scripts bloqués ou abandons avant affichage peuvent faire diverger les événements navigateur et le nombre de leads.
