# Accessibilité et mobile

Les layouts client et administration fournissent maintenant :

- une langue HTML correspondant à la locale active ;
- un lien d’évitement vers le contenu principal ;
- des indicateurs de focus visibles au clavier ;
- des zones tactiles d’au moins 44 px sur les appareils tactiles ;
- une réduction des animations selon `prefers-reduced-motion` ;
- une classe opt-in `table-responsive-card` pour transformer les tableaux en cartes sous 768 px.

Pour un tableau mobile, ajouter `table-responsive-card` au tableau et un attribut
`data-label` à chaque cellule. Les parcours accueil, profil, listes administratives,
formulaires et tickets doivent être vérifiés au clavier et avec Lighthouse/axe avant
chaque nouvelle version.
