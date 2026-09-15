<?php

return [
  'admin' => [
    'upgrades' => [
      'title' => 'Améliorations des services',
      'description' => 'Observez les améliorations d\'offres effectuées par les clients sur leurs services',
    ],
    'global_balance' => 'Solde global',
    'settings' => [
      'title' => 'Facturation',
      'description' => 'Configurez les options de facturation telles que la devise, les taxes et les conditions de paiement.',
      'success' => 'Les paramètres de facturation ont été enregistrés avec succès.',
      'webhookoncheckout' => 'Webhook au checkout',
      'tax' => 'Gestion de la TVA',
      'fields' => [
        'store_vat_enabled' => 'Activer la TVA',
        'customermustbeconfirmed' => 'Le client doit être confirmé pour commander',
        'toslink' => 'Lien des conditions générales de vente',
        'invoice_prefix' => 'Préfixe des factures',
        'billing_mode' => 'Mode de facturation',
        'billing_modes' => [
          'proforma' => 'Proforma',
          'invoice' => 'Facture',
        ],
        'add_setupfee_on_upgrade' => 'Ajouter des frais de configuration lors d\'une amélioration',
        'minimum_days_to_force_renewal_with_upgrade' => 'Délais minimum en jours pour forcer le renouvellement avec une amélioration',
        'remove_pending_invoice' => 'Action sur les factures en attente après (jours)',
        'remove_pending_invoice_help' => '0 pour désactiver',
        'allow_add_balance_to_invoices' => 'Autoriser l\'ajout de solde aux factures',
        'remove_pending_invoice_types' => [
          'delete' => 'Supprimer',
          'cancel' => 'Annuler',
        ],
        'vat_default_country' => 'Pays par défaut pour la TVA',
        'rates' => [
          'title' => 'Taux de TVA',
          'vat_rate_by_country' => 'Taux de TVA par pays du client',
          'vat_rate_fixed' => 'Taux de TVA fixe',
        ],
        'display_product_price' => [
          'title' => 'Affichage des prix des produits',
          'included' => 'Inclus - Le prix affiché est TTC',
          'excluded' => 'Exclus - Le prix affiché est HT',
        ],
        'store_mode_tax' => [
          'title' => 'Mode de taxe',
          'tax_included' => 'Taxe incluse',
          'tax_excluded' => 'Taxe exclue',
        ],
        'store_enabled' => 'Boutique activée',
        'store_redirect_url' => 'URL de redirection (si désactivée)',
      ],
    ],
    'title' => 'Facturation',
    'subheading' => 'Gestion des abonnements et des paiements',
    'subscriptions' => [
      'create' => [
        'title' => 'Créer un abonnement',
        'subheading' => 'Créer un abonnement pour un client',
      ],
      'title' => 'Abonnements',
      'description' => 'Gérez les abonnements à renouvellement automatique en cours des clients.',
      'last_payment_at' => 'Dernier paiement',
      'active_subscription' => 'Abonnements actifs',
      'services_empty' => 'Aucun service trouvé',
      'earn' => [
        'service_subscription_help' => 'Ces données sont basées depuis le :date',
        'monthly' => 'Abonnements',
        'monthly_earn' => 'Renouvellement',
        'subscription_and_services' => 'Abonnements et renouvellement de services',
        'monthly_help' => 'Les abonnements sont comptabilisés dans les renouvellements',
      ],
    ],
  ],
];
