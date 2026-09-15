<?php

use App\DTO\Core\Extensions\SectionDefinition as S;
use App\DTO\Core\Extensions\SectionField;

$partnerFields = [
    SectionField::text('title', 'Titre'),
    SectionField::textarea('subtitle', 'Description', rows: 3),
    SectionField::repeater('partners', 'Partenaires', [
        SectionField::image('image', 'Logo'),
        SectionField::url('url', 'URL', default: '#'),
        SectionField::text('alt', 'Texte alternatif'),
    ], 1, 12),
];

return [
    S::make('store_groups')
        ->default()
        ->protected()
        ->fields([SectionField::text('title', 'Titre'), SectionField::textarea('subtitle', 'Description')])
        ->toArray(),

    S::make('partners')
        ->thumbnail('https://api-nextgen.clientxcms.com/assets/ba9cd6e7-ba20-4e46-abce-c3a38c7a5663')
        ->default()
        ->protected(false)
        ->fields($partnerFields)
        ->toArray(),

    S::make('icons_tab')
        ->thumbnail('https://api-nextgen.clientxcms.com/assets/f7a09df8-684b-45bd-a7b2-d65728b83363')
        ->default()
        ->protected(false)
        ->fields(S::featureFields(4))
        ->toArray(),

    S::make('icons_center_aligned')
        ->thumbnail('https://api-nextgen.clientxcms.com/assets/f7a09df8-684b-45bd-a7b2-d65728b83363')
        ->protected(false)
        ->fields(S::featureFields(4))
        ->toArray(),

    S::make('card_contact_us')
        ->thumbnail('https://api-nextgen.clientxcms.com/assets/22a4302f-f2fd-49a8-bcc6-80e539efa688')
        ->default()
        ->defaultUrl('/store/basket')
        ->protected(false)
        ->fields([
            SectionField::text('card1_title', 'Titre de la carte 1'),
            SectionField::textarea('card1_description', 'Description de la carte 1'),
            SectionField::icon('card1_icon', 'Icône de la carte 1', 'bi-hdd-stack'),
            SectionField::url('card1_url', 'URL de la carte 1', default: '#'),
            SectionField::text('card2_title', 'Titre de la carte 2'),
            SectionField::textarea('card2_description', 'Description de la carte 2'),
            SectionField::icon('card2_icon', 'Icône de la carte 2', 'bi-hdd-stack'),
            SectionField::url('card2_url', 'URL de la carte 2', default: '#'),
        ])
        ->toArray(),

    S::make('statistics1_manual')
        ->thumbnail('https://api-nextgen.clientxcms.com/assets/3ae849db-7556-4fe6-9b46-7994e6204726')
        ->protected(false)
        ->fields([
            ...S::statFields(4, [1 => '0', 2 => '0', 3 => '0', 4 => '0']),
            SectionField::icon('stat1_icon', 'Icône 1', 'bi-hdd-stack'),
            SectionField::icon('stat2_icon', 'Icône 2', 'bi-hdd-stack'),
            SectionField::icon('stat3_icon', 'Icône 3', 'bi-hdd-stack'),
            SectionField::icon('stat4_icon', 'Icône 4', 'bi-hdd-stack'),
        ])
        ->toArray(),

    S::make('partners2')
        ->thumbnail('https://api-nextgen.clientxcms.com/assets/dd8c320c-529d-4a83-913f-70fdcc9c257b')
        ->protected(false)
        ->fields($partnerFields)
        ->toArray(),

    S::make('icons_tab2')
        ->thumbnail('https://api-nextgen.clientxcms.com/assets/dd8c320c-529d-4a83-913f-70fdcc9c257b')
        ->protected(false)
        ->fields([...S::headerFields(), ...S::featureFields(6)])
        ->toArray(),
];
