<?php

return [
  'admin' => [
    'upgrades' => [
      'title' => 'Service Improvements',
      'description' => 'See how customers are making improvements to their services.',
    ],
    'global_balance' => 'Overall balance',
    'settings' => [
      'title' => 'Invoicing',
      'description' => 'Store billing settings. Enable VAT, set currency, or other.',
      'success' => 'The billing settings have been saved successfully.',
      'webhookoncheckout' => 'Webhook at checkout',
      'tax' => 'VAT management',
      'fields' => [
        'store_vat_enabled' => 'Activate VAT',
        'customermustbeconfirmed' => 'Customer must be confirmed to order',
        'toslink' => 'Link to the general terms and conditions of sale',
        'invoice_prefix' => 'Invoice prefix',
        'billing_mode' => 'Billing method',
        'billing_modes' => [
          'proforma' => 'Proforma',
          'invoice' => 'Invoice',
        ],
        'add_setupfee_on_upgrade' => 'Add configuration charges during an upgrade',
        'minimum_days_to_force_renewal_with_upgrade' => 'Minimum time in days to force renewal with an improvement',
        'remove_pending_invoice' => 'Action on pending invoices after (days)',
        'remove_pending_invoice_help' => '0 to disable',
        'allow_add_balance_to_invoices' => 'Allow adding balance to invoices',
        'remove_pending_invoice_types' => [
          'delete' => 'Delete',
          'cancel' => 'Cancel',
        ],
        'vat_default_country' => 'Default country for VAT',
        'rates' => [
          'title' => 'VAT rate',
          'vat_rate_by_country' => 'VAT rate by customer\'s country',
          'vat_rate_fixed' => 'Fixed VAT rate',
        ],
        'display_product_price' => [
          'title' => 'Product price display',
          'included' => 'Included - The price displayed is inclusive of VAT',
          'excluded' => 'Excluded - The price displayed is excl. VAT',
        ],
        'store_mode_tax' => [
          'title' => 'Tax mode',
          'tax_included' => 'Tax included',
          'tax_excluded' => 'Tax excluded',
        ],
        'store_enabled' => 'Store enabled',
        'store_redirect_url' => 'Redirect URL (if disabled)',
      ],
    ],
    'title' => 'Invoicing',
    'subheading' => 'Subscription and payment management',
    'subscriptions' => [
      'create' => [
        'title' => 'Create a subscription',
        'subheading' => 'Create a subscription for a customer',
      ],
      'title' => 'Subscriptions',
      'description' => 'Subscription Management',
      'last_payment_at' => 'Last payment',
      'active_subscription' => 'Active subscriptions',
      'services_empty' => 'No services found',
      'earn' => [
        'service_subscription_help' => 'This data is based from :date',
        'monthly' => 'Monthly subscriptions',
        'monthly_earn' => 'Monthly renewals',
        'subscription_and_services' => 'Subscriptions and Service Renewals',
        'monthly_help' => 'Subscriptions count towards monthly renewals',
      ],
    ],
  ],
];
