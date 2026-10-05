# Accueil WordPress et WooCommerce

Trois sections de cartes présentent les huit expertises WordPress, les sept expertises WooCommerce et les sept autres services, avec les mêmes icônes que le menu. La FAQ d’accueil est remplacée par une présentation de l’agence et quatre indicateurs éditables dans Gutenberg.

Les quatre valeurs sont volontairement fixées à zéro à la demande de l’utilisateur. L’animation au défilement porte sur leur apparition, sans générer de valeurs intermédiaires fictives. Elle respecte la préférence de réduction des mouvements. Sans JavaScript, les chiffres restent visibles.

Pour modifier les valeurs, éditer les paragraphes de la section agence dans Pages → Accueil. Conserver les classes `agency-stat` et `agency-stat-number` pour l’animation. Utiliser uniquement des chiffres vérifiés.

Sur une installation existante, sauvegarder la base et les fichiers, mettre à jour le thème, puis exécuter `wp eval-file /chemin/du/projet/tools/update-home.php`. Le script reconnaît la version précédente de l’accueil de cette livraison et refuse d’écraser un contenu personnalisé. Sur une installation neuve, `seed-content.php` crée directement la nouvelle version.

Régénérer ensuite l’aperçu autonome avec `tools/export-offline.cjs` : il intègre les logos et le script d’animation sans requête externe.

## Sources des logos

- WordPress : `wp-admin/images/wordpress-logo.svg`, issu de la distribution WordPress locale. Référence officielle : https://wordpress.org/about/logos/
- WooCommerce : logo Woo officiel, https://woocommerce.com/wp-content/uploads/2025/07/Woo_logo_color.svg, référencé sur la page d’accueil https://woocommerce.com/ le 1 octobre 2026.

Ces marques identifient les technologies présentées et ne constituent pas une certification ou un partenariat de l’agence.

## Identité du site
Le titre devient Les Experts Wordpress. Après mise à jour du thème, exécuter tools/update-branding.php avec WP-CLI pour renommer une installation existante et ajouter le repère 04 · Sécuriser sans remplacer les autres contenus. Le logo d’en-tête reprend le carré bleu W de la bannière.


## Grille des technologies
Grille monochrome de huit logos après les autres services : WordPress, WooCommerce, ChatGPT, Claude, JavaScript, Elementor, GitHub et PHP. Les icônes OpenAI proviennent de Simple Icons 13.0.0 ; Claude, JavaScript, Elementor, GitHub et PHP de Simple Icons 16.33.0 (CDN jsDelivr). Les SVG sont fournis localement, et intégrés dans la feuille de style pour la copie autonome. La grille présente des outils, pas des références clients ni des certifications. Pour une installation existante, appliquer tools/update-technologies.php avec WP-CLI. Le lien Le journal a été retiré de la navigation principale.


## Extension du catalogue : IA et sécurisation
La section Nos autres services apparaît sous WooCommerce avec les pictogrammes du menu. Deux pages sont ajoutées : /services/ia-wordpress-woocommerce/ et /services/securisation-woocommerce/. Le menu et le formulaire de devis proposent ces prestations.

Sur une installation existante, sauvegarder la base, mettre à jour le thème et l’extension, puis exécuter avec WP-CLI : wp eval-file /chemin/du/projet/tools/update-catalog-expansion.php. Les pages personnalisées sont conservées avec un avertissement pour fusion manuelle. Régénérer ensuite la copie autonome.

Validation : 24 pages de services, 28 liens de devis ; export autonome de 33 pages sans ressource distante.

Les deux blocs téléphone sont également présents sous le formulaire Contact. Sur une installation existante, appliquer tools/update-contact-phones.php avec WP-CLI.
