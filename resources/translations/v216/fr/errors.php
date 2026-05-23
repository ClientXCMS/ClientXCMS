<?php

/*
 * v2.16 — Traductions des pages d'erreur redessinées.
 * Voir resources/translations/v216/README.md.
 */

return [
    'common' => [
        'home' => 'Retour à l\'accueil',
        'dashboard' => 'Retour au tableau de bord',
        'support' => 'Contacter le support',
        'previous' => 'Revenir en arrière',
        'status_code' => 'Erreur :code',
    ],

    '401' => [
        'title' => 'Vous n\'êtes pas connecté',
        'heading' => 'Authentification requise',
        'description' => 'Cette page est réservée aux utilisateurs connectés. Veuillez vous connecter et réessayer.',
        'sign_in' => 'Se connecter',
    ],
    '403' => [
        'title' => 'Accès refusé',
        'heading' => 'Interdit',
        'description' => 'Vous n\'avez pas la permission d\'accéder à cette ressource. Si vous pensez qu\'il s\'agit d\'une erreur, contactez le support.',
    ],
    '404' => [
        'title' => 'Page introuvable',
        'heading' => 'Nous avons cherché partout…',
        'description' => 'La page que vous cherchez a été déplacée ou n\'a jamais existé. Utilisez l\'un des boutons ci-dessous pour reprendre votre route.',
    ],
    '419' => [
        'title' => 'Votre session a expiré',
        'heading' => 'Session expirée',
        'description' => 'Pour votre sécurité, nous avons fermé votre session après une période d\'inactivité. Veuillez recharger la page et soumettre le formulaire à nouveau.',
        'reload' => 'Recharger la page',
    ],
    '422' => [
        'title' => 'Requête invalide',
        'heading' => 'Quelque chose cloche',
        'description' => 'Nous n\'avons pas pu traiter votre demande car certaines données sont manquantes ou incorrectes. Revenez en arrière et réessayez.',
    ],
    '429' => [
        'title' => 'Trop de requêtes',
        'heading' => 'Doucement…',
        'description' => 'Vous avez effectué trop de requêtes en peu de temps. Veuillez patienter un instant avant de réessayer.',
    ],
    '500' => [
        'title' => 'Une erreur est survenue de notre côté',
        'heading' => 'Erreur interne du serveur',
        'description' => 'Une erreur inattendue a été enregistrée. Notre équipe a été notifiée. Veuillez réessayer dans quelques minutes.',
    ],
    '503' => [
        'title' => 'Maintenance en cours',
        'heading' => 'Service indisponible',
        'description' => 'Nous mettons à jour la plateforme. Le service sera de retour en ligne sous peu. Merci de votre patience.',
    ],
];
