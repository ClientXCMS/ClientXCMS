@php
    $partyTitle = $party['legal_name'] ?? null;
    $isBuyer = ($role ?? 'buyer') === 'buyer';
@endphp

@if (($showTitle ?? true) && $partyTitle)
    <div class="font-semibold text-gray-800 dark:text-gray-200">{{ $partyTitle }}</div>
@endif
@if ($isBuyer && !empty($party['name']) && $party['name'] !== $partyTitle)
    <div>{{ $party['name'] }}</div>
@endif
@if ($isBuyer && !empty($party['email']))
    <div>{{ $party['email'] }}</div>
@endif
@if ($isBuyer)
    <div>{{ $party['address'] }}@if (!empty($party['address2'])) {{ $party['address2'] }}@endif</div>
    <div>@if (!empty($party['region'])){{ $party['region'] }} · @endif{{ $party['zipcode'] }} {{ $party['city'] }}</div>
    <div>{{ \App\Helpers\Countries::names()[$party['country']] ?? $party['country'] }}</div>
@elseif (!empty($party['address']))
    <div>{!! nl2br(e($party['address'])) !!}</div>
@endif

@foreach ([
    'siren' => __('einvoicing.profile.siren'),
    'siret' => __('einvoicing.profile.siret'),
    'rna_number' => __('einvoicing.profile.rna_number'),
    'vat_number' => __('einvoicing.profile.vat_number'),
    'tax_registration_number' => __('einvoicing.profile.tax_registration_number'),
] as $field => $label)
    @if (!empty($party[$field]))
        <div><span class="font-medium text-gray-700 dark:text-gray-300">{{ $label }} :</span> {{ $party[$field] }}</div>
    @endif
@endforeach

@if ($isBuyer && !empty($party['additional_details']))
    <div class="mt-2">{!! nl2br(e($party['additional_details'])) !!}</div>
@endif
