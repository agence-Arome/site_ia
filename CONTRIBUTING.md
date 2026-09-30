# Travailler à deux

## Ce que GitHub partagera

Le code du thème, l’extension, les contenus initiaux, les tests, la documentation et `index.html` (aperçu autonome). Chaque personne clone le même dépôt sur son ordinateur.

Git ne synchronise pas la base WordPress, les médias téléversés, les réglages sauvegardés dans l’administration ou les leads. Chaque installation locale possède sa propre base. Utiliser une préproduction commune pour valider les contenus éditoriaux ; ne jamais publier de données clients dans le dépôt.

## Première installation

1. Accepter l’invitation au dépôt privé avec son propre compte GitHub.
2. Cloner le dépôt avec GitHub Desktop ou `git clone URL_DU_DEPOT`.
3. Pour consulter le rendu uniquement, ouvrir `index.html` dans un navigateur.
4. Pour développer et tester les fonctionnalités, suivre le README : Docker, `.env` personnel, installation de WordPress et activation du thème et de l’extension.
5. Configurer son propre nom et son e-mail Git. Ne pas partager les mots de passe GitHub.

## Une branche par modification

Avant une nouvelle tâche :

```sh
git switch main
git pull --ff-only
git switch -c feature/nom-de-la-modification
```

Modifier, vérifier, puis enregistrer uniquement les fichiers de la tâche :

```sh
git add chemin/du/fichier
git commit -m "Décrire le changement"
git push -u origin feature/nom-de-la-modification
```

Ouvrir une pull request sur GitHub vers `main`. L’autre personne relit les changements et les résultats de tests avant fusion. Éviter de modifier simultanément les mêmes fichiers ; annoncer la zone prise en charge.

En cas de conflit, conserver les deux intentions, résoudre localement et retester. Ne pas forcer un push sur `main` et ne pas écraser les modifications de l’autre personne.

## Mettre à jour l’aperçu autonome

L’HTML est un export, pas la source du thème. Modifier d’abord le thème, l’extension ou les contenus, puis vérifier le site local. Avec Node 22+ :

```sh
npm run preview:export
```

Par défaut, l’export lit `http://127.0.0.1:8080`. Adapter `WP_PREVIEW_URL` à l’URL de son WordPress, par exemple en PowerShell :

```powershell
$env:WP_PREVIEW_URL = 'http://localhost:8080'
npm run preview:export
```

Il remplace `index.html` par une version comprenant les pages publiques attendues, styles intégrés et formulaires sans envoi. Contrôler le résultat avant de le committer. Les données de l’administration ne sont pas exportées.

## Contrôles et droits GitHub

La vérification automatique fournie contrôle PHP et les conventions WordPress. Elle ne déploie rien et ne remplace pas la recette fonctionnelle locale décrite dans le README.

Le propriétaire du dépôt devra ajouter le second compte avec le droit d’écriture, puis activer, si le forfait le permet, la protection de `main` : pull request obligatoire, une approbation et contrôles réussis. Ces réglages ne sont pas activés par les fichiers seuls.

Un dépôt privé protège le code contre une publication involontaire. GitHub ne remplace pas l’hébergement WordPress ; les fonctionnalités serveur ne fonctionnent pas dans GitHub Pages. Toute publication du site reste une opération séparée, à autoriser explicitement.
