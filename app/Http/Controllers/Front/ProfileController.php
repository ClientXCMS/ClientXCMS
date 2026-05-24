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
 * Learn more about CLIENTXCMS License at:
 * https://clientxcms.com/eula
 *
 * Year: 2025
 */

namespace App\Http\Controllers\Front;

use App\Helpers\Countries;
use App\Http\Requests\Profile\DeleteAccountRequest;
use App\Http\Requests\Profile\ProfilePasswordRequest;
use App\Http\Requests\Profile\ProfileUpdateRequest;
use App\Models\Account\Customer;
use App\Models\ActionLog;
use App\Services\Account\AccountDeletionService;
use App\Services\Account\GdprExportService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
            'ownedAccountAccesses' => $request->user('web')->ownedAccountAccesses()->with(['subCustomer', 'services'])->orderBy('created_at', 'desc')->get(),
            'receivedAccountAccesses' => $request->user('web')->receivedAccountAccesses()->with(['owner', 'services'])->orderBy('created_at', 'desc')->get(),
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
        $request->user('web')->revokeAllTwoFactorTrust();
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
        $request->user('web')->trustTwoFactorIp($request->ip(), $request->userAgent());

        return redirect()->route('front.profile.index')->with('success', __('client.profile.2fa.enabled'));
    }

    public function save2faOptions(Request $request): RedirectResponse
    {
        $request->user('web')->setTwoFactorEmailOnNewIp($request->has('2fa_email_new_ip'));
        $request->user('web')->trustTwoFactorIp($request->ip(), $request->userAgent());

        return redirect()->route('front.profile.index')->with('success', __('client.profile.2fa.options_saved'));
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
        }, '2fa_recovery_codes_' . \Str::slug(config('app.name')) . '.txt');
    }

    public function deleteAccount(DeleteAccountRequest $request): RedirectResponse
    {
        $customer = $request->user('web');
        $deletionService = new AccountDeletionService;

        try {
            $deletionService->delete($customer);
            auth('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('front.index')
                ->with('success', __('client.profile.delete.success'));
        } catch (\Exception $e) {
            return redirect()->route('front.profile.delete')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Save or update the security question for the current user.
     */
    public function saveSecurityQuestion(Request $request): RedirectResponse
    {
        $request->validate([
            'security_question_id' => ['required', 'exists:security_questions,id'],
            'security_answer' => ['required', 'string', 'min:2', 'max:100'],
            'currentpassword_sq' => ['required', 'current_password'],
        ]);

        $request->user('web')->setSecurityQuestion(
            (int) $request->security_question_id,
            $request->security_answer
        );

        return redirect()->route('front.profile.index')
            ->with('success', __('client.profile.security_question_saved'));
    }

    /**
     * v2.16 — GDPR Article 20 (right to data portability).
     *
     * Builds a ZIP containing the customer's profile, invoices,
     * services, tickets and API token names; flashes a one-shot
     * signed download URL on the redirect-back. The link expires
     * after 24 h.
     */
    public function export(Request $request, GdprExportService $service): RedirectResponse
    {
        $customer = $request->user('web');
        $relativePath = $service->buildArchive($customer);

        return redirect()->route('front.profile.index')
            ->with('success', __('v216::gdpr.export.ready'))
            ->with('gdpr_export_url', $service->signedUrl($relativePath));
    }

    /**
     * Serve a previously-built GDPR export. The route is signed and
     * scoped to the authenticated customer's storage prefix.
     */
    public function downloadExport(Request $request, string $path): \Symfony\Component\HttpFoundation\BinaryFileResponse|RedirectResponse
    {
        // Path must live under gdpr/{customer_id}/...
        $customer = $request->user('web');
        $prefix = GdprExportService::STORAGE_DIR . '/' . $customer->id . '/';
        if (! str_starts_with($path, $prefix) || str_contains($path, '..')) {
            abort(403);
        }
        if (! Storage::disk('local')->exists($path)) {
            return redirect()->route('front.profile.index')
                ->with('error', __('v216::gdpr.export.expired'));
        }

        return response()->download(
            Storage::disk('local')->path($path),
            'data-export.zip',
            ['Content-Type' => 'application/zip']
        );
    }
}
