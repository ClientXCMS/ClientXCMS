<?php
/*
 * This file is part of the CLIENTXCMS project.
 * It is the property of the CLIENTXCMS association.
 *
 * Personal and non-commercial use of this source code is permitted.
 * However, any use in a project that generates profit (directly or indirectly),
 * or any reuse for commercial purposes, requires prior authorization from CLIENTXCMS.
 *
 * To request permission or for more information, please contact our support:
 * https://clientxcms.com/client/support
 *
 * Year: 2025
 */
namespace App\Http\Controllers\Front;

use App\Helpers\Countries;
use App\Http\Requests\Profile\ProfilePasswordRequest;
use App\Http\Requests\Profile\ProfileUpdateRequest;
use App\Models\Account\Customer;
use App\Models\ActionLog;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use PragmaRX\Google2FAQRCode\Google2FA;

class ProfileController extends \App\Http\Controllers\Controller
{
    public function show(Request $request)
    {

        if (app('extension')->extensionIsEnabled('socialauth')) {
            $providers = \App\Addons\SocialAuth\Models\ProviderEntity::where('enabled', true)->get();
        } else {
            $providers = [];
        }
        if (! $request->user()->twoFactorEnabled()) {
            $google = new Google2FA;
            $secret = $request->session()->get('2fa_secret', $google->generateSecretKey());
            $google->setQrcodeService(
                new \PragmaRX\Google2FAQRCode\QRCode\Bacon(
                    new \BaconQrCode\Renderer\Image\SvgImageBackEnd
                )
            );

            $qrcode = $google->getQRCodeInline(
                config('app.name'),
                $request->user()->email,
                $secret
            );
            $request->session()->put('2fa_secret', $secret);
        } else {
            $qrcode = null;
        }

        return view('front.profile.edit', [
            'user' => $request->user('web'),
            'countries' => Countries::names(),
            'locales' => \App\Services\Core\LocaleService::getLocalesNames(),
            'providers' => $providers,
            'qrcode' => $qrcode,
            'code' => $request->session()->get('2fa_secret'),
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user('web')->fill($request->validated());

        if ($request->user('web')->isDirty('email')) {
            $request->user('web')->email_verified_at = null;
        }

        $request->user('web')->save();

        return redirect()->route('front.profile.index')->with('success', __('client.profile.updated'));
    }

    public function password(ProfilePasswordRequest $request)
    {
        $request->user('web')->update(['password' => $request->password]);
        try {
            \Auth::logoutOtherDevices($request->password);
        } catch (AuthenticationException $e) {

        }

        return redirect()->route('front.profile.index')->with('success', __('client.profile.changepassword'));
    }

    public function save2fa(Request $request)
    {
        $request->validate([
            '2fa' => ['required', 'string', 'size:6', new \App\Rules\Valid2FACodeRule($request->session()->get('2fa_secret'))],
        ]);
        if ($request->user('web')->twoFactorEnabled()) {
            ActionLog::log(ActionLog::TWO_FACTOR_DISABLED, Customer::class, $request->user()->id, null, $request->user()->id);

            $request->user('web')->twoFactorDisable();

            return redirect()->route('front.profile.index')->with('success', __('client.profile.2fa.disabled'));
        }
        ActionLog::log(ActionLog::TWO_FACTOR_ENABLED, Customer::class, $request->user()->id, null, $request->user()->id);
        $request->user('web')->twoFactorEnable($request->session()->get('2fa_secret'));

        return redirect()->route('front.profile.index')->with('success', __('client.profile.2fa.enabled'));
    }

    public function downloadCodes(Request $request)
    {
        $codes = \Auth::user()->twoFactorRecoveryCodes();
        ActionLog::log(ActionLog::TWO_FACTOR_RECOVERY_CODES_GENERATED, Customer::class, $request->user()->id, null, $request->user()->id);

        return response()->streamDownload(function () use ($codes) {
            $codes = collect($codes)->map(function ($code) {
                return $code;
            });
            echo $codes->join("\n");
        }, '2fa_recovery_codes_'.\Str::slug(config('app.name')).'.txt');
    }
}
