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

Publication vérifiée le 8 octobre 2026 : six pages créées, 41 contenus précédents conservés. Titre de la page de navigation : Notre accompagnement WordPress dans le sud de la France. Les cinq pages départementales présentent un seul H1, une métadescription et une canonique correspondant à leur URL. Lien ajouté au pied de page du site publié. Le menu principal reste inchangé.

Prochaine série demandée : Var (Toulon, Fréjus, Draguignan), Alpes-Maritimes (Nice, Antibes, Cannes), Aude (Narbonne, Carcassonne, Castelnaudary), Pyrénées-Orientales (Perpignan). Ces pages ne sont pas encore créées.

## Deuxième série préparée

Les pages /agence-wordpress-var/, /agence-wordpress-alpes-maritimes/, /agence-wordpress-aude/ et /agence-wordpress-pyrenees-orientales/ sont créées dans les sources et dans l’aperçu bleu. Les villes demandées figurent dans les contenus visibles. Leurs angles abordent respectivement les temps forts et le mobile, le multilingue et les visuels, le catalogue et l’autonomie éditoriale, la reprise et les évolutions progressives.

La page de navigation inclut désormais neuf départements. Sur WordPress, l’importeur crée les quatre nouvelles pages mais conserve la page de navigation existante : son contenu et sa métadescription doivent être mis à jour dans Gutenberg. La session est déconnectée au moment de la préparation ; la publication de cette série reste à effectuer. Les tests des 51 contenus sources et des 50 pages autonomes passent.

Publication de la deuxième série vérifiée le 8 octobre 2026 : 4 contenus créés et 47 contenus existants conservés. La page /zones-accompagnement/ a été actualisée dans Gutenberg avec les neuf liens et sa nouvelle métadescription Yoast. Les quatre nouvelles pages publiques présentent les villes demandées, un seul H1, leur titre SEO, une métadescription et une URL canonique propre. Le menu principal reste inchangé. Aucun score Yoast vert spécifique à cette série n’est annoncé sans analyse de l’éditeur.

