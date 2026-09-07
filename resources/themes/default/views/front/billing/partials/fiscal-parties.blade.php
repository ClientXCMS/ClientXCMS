@php
    $seller = $fiscalParties['seller'];
    $buyer = $fiscalParties['buyer'];
    $buyerTitle = $buyer['legal_name'] ?: $buyer['name'];
@endphp

<table class="addresses-table">
    <tr>
        <td>
            <h3>{{ $seller['legal_name'] ?: setting('app.name') }}</h3>
            @if (!empty($seller['address']))
                <pre>{{ $seller['address'] }}</pre>
            @endif
            @if (!empty($seller['siren']))
                <span class="detail-label">{{ __('einvoicing.profile.siren') }} :</span> {{ $seller['siren'] }}<br>
            @endif
            @if (!empty($seller['siret']))
                <span class="detail-label">{{ __('einvoicing.profile.siret') }} :</span> {{ $seller['siret'] }}<br>
            @endif
            @if (!empty($seller['vat_number']))
                <span class="detail-label">{{ __('einvoicing.profile.vat_number') }} :</span> {{ $seller['vat_number'] }}
            @endif
        </td>
        <td>
            <h3>{{ __('client.invoices.billto', ['name' => $buyerTitle]) }}</h3>
            @if ($buyer['legal_name'] && $buyer['name'])
                {{ $buyer['name'] }}<br>
            @endif
            @if (!empty($buyer['email']))
                {{ $buyer['email'] }}<br>
            @endif
            {{ $buyer['address'] }}@if (!empty($buyer['address2'])) {{ $buyer['address2'] }}@endif<br>
            @if (!empty($buyer['region'])){{ $buyer['region'] }} · @endif{{ $buyer['zipcode'] }} {{ $buyer['city'] }}<br>
            {{ \App\Helpers\Countries::names()[$buyer['country']] ?? $buyer['country'] }}<br>

            @foreach ([
                'siren' => __('einvoicing.profile.siren'),
                'siret' => __('einvoicing.profile.siret'),
                'rna_number' => __('einvoicing.profile.rna_number'),
                'vat_number' => __('einvoicing.profile.vat_number'),
                'tax_registration_number' => __('einvoicing.profile.tax_registration_number'),
            ] as $field => $label)
                @if (!empty($buyer[$field]))
                    <span class="detail-label">{{ $label }} :</span> {{ $buyer[$field] }}<br>
                @endif
            @endforeach

            @if (!empty($buyer['additional_details']))
                <br>{!! nl2br(e($buyer['additional_details'])) !!}
            @endif
        </td>
    </tr>
</table>
