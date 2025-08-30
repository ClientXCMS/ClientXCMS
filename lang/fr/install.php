<?php

return [
    'install' => 'Installation',
    'step' => 'Étape',
    'settings' => [
        'title' => 'Paramètres',
        'client_id' => 'ID client',
        'client_secret' => 'Secret client',
        'hosting_name' => 'Nom de la société',
        'connect' => 'Se connecter',
        'locales' => 'Langues',
        'infolicense' => 'Pour obtenir un ID client et un secret client, vous devez avoir une licence CLIENTXCMS. <a href=":link" target="_blank" class="underline">Cliquez ici pour récupérer vos identifiants gratuitement.</a>',
        'migrationwarning' => 'Attention : la base de données n\'a pas été migrée. Veuillez exécuter "php artisan migrate --force --seed" pour migrer la base de données.',
        'detecteddomain' => 'Domaine détecté : :domain. Assurez-vous que le domaine correspond exactement à celui de votre licence (y compris le sous-domaine le cas échéant).',
        'eula' => 'En consultant cette page, vous acceptez les termes de la licence CLIENTXCMS disponible sur clientxcms.com/eula.'
    ],
    'register' => [
        'title' => 'Inscription',
        'btn' => 'Créer le compte',
        'telemetry' => 'Envoyer des données de télémétrie anonymisées. Cela nous aide à améliorer le CMS et à corriger les bugs. Vous pouvez désactiver cette option plus tard dans les paramètres.',
    ],
    'summary' => [
        'title' => 'Résumé',
        'btn' => 'Terminer',
    ],
    'submit' => 'Envoyer',
    'password' => 'Mot de passe',
    'firstname' => 'Prénom',
    'lastname' => 'Nom de famille',
    'email' => 'E-mail',
    'password_confirmation' => 'Confirmation du mot de passe',
    'authentication' => 'Authentification',
    'extensions' => 'Extensions',
];
