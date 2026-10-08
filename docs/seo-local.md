# Pages départementales

Créées le 8 octobre 2026. Base de travail éditoriale : une page par département, pas une série de pages identiques par commune. L’implantation réelle annoncée reste Avignon. Aucun établissement, avis client, projet local ou résultat n’est inventé.

| URL | Villes mentionnées dans le contenu visible |
| --- | --- |
| /agence-wordpress-vaucluse/ | Avignon, Carpentras, Orange, Cavaillon, L’Isle-sur-la-Sorgue |
| /agence-wordpress-bouches-du-rhone/ | Marseille, Aix-en-Provence, Arles, Tarascon |
| /agence-wordpress-gard/ | Nîmes, Alès, Beaucaire |
| /agence-wordpress-herault/ | Montpellier, Béziers, Sète |
| /agence-wordpress-drome/ | Valence, Montélimar, Romans-sur-Isère |

La page /zones-accompagnement/ permet de parcourir ces pages depuis le pied de page. Chaque page apporte une introduction, les villes, un résumé des prestations avec liens, des conseils de préparation différents et un contact direct. Les conseils illustrent des besoins possibles et ne prétendent pas décrire des clients ou des spécialités économiques locales établies.

Tarascon se trouve dans les Bouches-du-Rhône, Beaucaire dans le Gard. Romans est présenté sous son nom complet Romans-sur-Isère.

## Intégration

Les six nouveaux contenus Gutenberg figurent dans tools/content.json et dans le fichier de données de l’importeur. Le modèle service fournit le H1 ; les contenus utilisent des H2 et H3. Les métadonnées sont dans seo et reprises dans tools/seo/page-map.json. L’importeur conserve les pages existantes et crée uniquement les pages manquantes. Sur le site existant, ne pas cocher l’option de reconfiguration de l’accueil et des permaliens. Le thème contient le lien de pied de page ; un pied de page personnalisé enregistré dans WordPress peut nécessiter l’ajout manuel de ce lien.

Le fichier index.html bleu propose également ces pages. La variante verte reste inchangée. L’instantané de migration SEO d’octobre n’est pas modifié : il concerne les contenus antérieurs.

## Validation et publication

Les tests seo-content vérifient les blocs, les liens internes, les métadonnées et les doublons. Le score Yoast des nouvelles pages doit être contrôlé dans WordPress après leur import ; aucun score ni gain de position n’est garanti. Contrôler aussi le H1, la canonique propre à chaque page et la présence dans le sitemap sur le site publié.

Une session WordPress connectée est nécessaire pour effectuer l’intégration en ligne. La création locale ne vaut pas publication.

## Références éditoriales

- Agence et périmètre annoncé : https://www.arome.fr/
- Google : éviter les pages locales similaires servant uniquement d’intermédiaires https://developers.google.com/search/docs/essentials/spam-policies#doorways

L’objectif est une navigation utile par département. La présence de villes dans les textes ne garantit pas leur affichage dans un extrait Google, ni une visibilité dans Google Maps.
