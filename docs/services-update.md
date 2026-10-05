# Services WordPress, WooCommerce et autres prestations

La navigation principale affiche directement « Expert WordPress » (8 pages), « Expert WooCommerce » (6 pages) et « Nos autres services » (6 pages). Une page récapitulative présente les autres services. Les six anciennes adresses WordPress sont conservées. Chaque lien de prestation comporte une icône décorative et une description Gutenberg native ; la rubrique parente « Services » a été retirée du menu.

Les textes se modifient dans Pages avec Gutenberg. Le menu par défaut se trouve dans le composant d’en-tête du thème et peut être personnalisé dans l’éditeur du site. Les prestations du devis sont définies dans l’extension métier.

## Appliquer à une installation existante

Sauvegarder la base et les fichiers, puis tester sur une copie locale ou de préproduction. Mettre à jour le thème, l’extension et les outils du dépôt, puis exécuter depuis l’installation WordPress :

```sh
wp eval-file /chemin/du/projet/tools/update-services.php
```

Le script ajoute les pages absentes. Il met à jour l’accueil et la page Services uniquement si leur contenu correspond au contenu initial livré. Les pages personnalisées sont conservées avec un avertissement : intégrer alors les nouveaux liens manuellement depuis `tools/content.json`. Les autres pages existantes ne sont pas écrasées. Une seconde exécution ne crée pas de doublons.

Si l’en-tête ou la navigation ont été personnalisés dans l’administration WordPress, fusionner le nouveau menu dans l’éditeur du site ; les personnalisations enregistrées en base peuvent prendre le pas sur les fichiers du thème.

## Aperçu autonome

Après application sur WordPress, régénérer `index.html` :

```sh
WP_PREVIEW_URL=http://127.0.0.1:8080 node tools/export-offline.cjs
```

Distribuer ce nouveau fichier aux deux personnes. Il comporte 31 pages de consultation ; les formulaires n’envoient aucune demande. Le fichier HTML n’est pas une base WordPress et ses modifications ne sont pas réimportées dans Gutenberg.

## Vérification

```sh
WP_TEST_URL=http://127.0.0.1:8080 node tests/service-catalog.cjs
```

Le contrôle vérifie les 22 pages de la rubrique Services, le titre principal unique, les descriptions SEO et la présélection des prestations sur les 26 liens vers le devis. Vérifier également l’ouverture des groupes à la souris et au clavier, la fermeture avec Échap, et la navigation mobile dans WordPress et l’aperçu autonome.

Cette mise à jour n’installe ni WooCommerce ni Elementor : elle présente les prestations proposées. Aucune boutique ni fonction de paiement n’est ajoutée au site de l’agence.

## Communication digitale et création
Sept pages ajoutées dans deux rubriques dépliables du menu Nos autres services, avec pictogrammes, descriptions et sélection dans le devis. Les pages de regroupement Services et Nos autres services sont enrichies. Sur une installation existante, sauvegarder puis mettre à jour thème et extension et exécuter : wp eval-file /chemin/du/projet/tools/update-communication.php. Le script conserve les contenus personnalisés et signale les fusions nécessaires.
Validation locale : 31 pages de services et 35 liens de devis contrôlés. Export autonome : 40 pages.


Le menu Nos autres services est désormais un méga-menu de trois colonnes sur ordinateur : NOS SERVICES WORDPRESS (dont IA), NOS SERVICES DE COMMUNICATION DIGITALE et NOS SERVICES CRÉATIFS. Sur petit écran les catégories sont empilées. Le thème rend les catégories en intitulés non interactifs, en conservant les liens et les icônes.
