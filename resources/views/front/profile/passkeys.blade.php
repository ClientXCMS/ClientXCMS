@vite('resources/global/js/passkeys.js')
<section class="mt-6 border-t border-gray-200 dark:border-gray-700 pt-6">
    <h3 class="text-base font-semibold text-gray-800 dark:text-white">{{ __('auth.passkeys.title') }}</h3>
    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('auth.passkeys.description') }}</p>
    @php($passkeyConfirmed = (time() - session('auth.password_confirmed_at', 0)) <= setting('password_timeout', config('auth.password_timeout', 10800)))
        @unless($passkeyConfirmed)
        <a href="{{ route('password.confirm') }}" class="btn-primary mt-4 inline-flex">{{ __('auth.passkeys.confirm_before_management') }}</a>
        @else
        <div class="mt-4 space-y-3">
            @forelse($passkeys as $passkey)
            <div class="flex items-center justify-between gap-4 rounded-lg border border-gray-200 dark:border-gray-700 p-3">
                <div><strong>{{ $passkey->name }}</strong>
                    <p class="text-xs text-gray-500">{{ $passkey->authenticator ?: __('auth.passkeys.unknown_authenticator') }} · {{ __('auth.passkeys.created_at', ['date' => $passkey->created_at->format('d/m/Y')]) }}@if($passkey->last_used_at) · {{ __('auth.passkeys.last_used_at', ['date' => $passkey->last_used_at->format('d/m/Y H:i')]) }}@endif</p>
                </div>
                <form method="POST" action="{{ route('front.profile.passkeys.destroy', $passkey) }}">@csrf @method('DELETE')<button class="text-sm text-red-600" type="submit">{{ __('auth.passkeys.delete') }}</button></form>
            </div>
            @empty
            <p class="text-sm text-gray-500">{{ __('auth.passkeys.empty') }}</p>
            @endforelse
        </div>
        <div class="mt-4" data-passkey-register data-error-message="{{ __('auth.passkeys.error') }}" data-name-required="{{ __('auth.passkeys.name_required') }}">
            @include('shared/input', [
            'name' => 'passkey_name',
            'label' => __('auth.passkeys.name'),
            'placeholder' => __('auth.passkeys.name_placeholder'),
            'required' => true,
            'attributes' => ['data-passkey-name' => true, 'maxlength' => 255],
            ])
            <button type="button" data-passkey-register-button class="btn-primary mt-3">{{ __('auth.passkeys.add') }}</button>
            <p data-passkey-error hidden role="alert" class="mt-2 text-sm text-red-600"></p>
        </div>
        @endunless
</section>