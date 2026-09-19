<?php

return [
  'product' => [
    'no_data' => 'Aucune information supplémentaire n’est requise à la commande.',
  ],
  'config' => [
    'server' => 'Serveur Mailcow',
    'max_domains' => 'Domaines maximum',
    'max_mailboxes' => 'Boîtes maximum par domaine',
    'max_aliases' => 'Alias maximum par domaine',
    'default_quota_mib' => 'Quota par défaut d’une boîte (Mio)',
    'max_quota_mib' => 'Quota maximal d’une boîte (Mio)',
    'domain_quota_mib' => 'Quota total du domaine (Mio)',
    'rate_limit_value' => 'Valeur de la limite d’envoi',
    'rate_limit_frame' => 'Période de la limite d’envoi',
    'backup_mx' => 'Serveur MX de secours',
    'relay_all_recipients' => 'Relayer tous les destinataires',
    'seconds' => 'Secondes',
    'minutes' => 'Minutes',
    'hours' => 'Heures',
  ],
  'panel' => [
    'title' => 'Domaines e-mail',
    'permission' => 'Gérer les domaines Mailcow',
    'usage' => ':used domaine(s) utilisé(s) sur :max',
    'empty' => 'Aucun domaine n’est encore configuré.',
    'mailboxes' => 'Boîtes',
    'aliases' => 'Alias',
    'admin' => 'Administrateur',
    'password' => 'Mot de passe',
    'status_active' => 'Actif',
    'status_suspended' => 'Suspendu',
  ],
  'actions' => [
    'add' => 'Ajouter le domaine',
    'delete' => 'Supprimer',
    'delete_confirm' => 'Supprimer définitivement ce domaine et toutes ses données e-mail ?',
    'dns' => 'Exporter les DNS',
    'domain_admin' => 'Administration Mailcow',
  ],
  'messages' => [
    'domain_created' => 'Le domaine Mailcow a été créé.',
    'domain_deleted' => 'Le domaine Mailcow a été supprimé.',
  ],
  'errors' => [
    'domain_exists' => 'Ce domaine est déjà provisionné.',
    'domain_limit' => 'La limite de domaines de ce service est atteinte.',
    'config_missing' => 'La configuration du produit Mailcow est introuvable.',
    'api' => 'Mailcow n’a pas pu traiter la demande.',
  ],
];
