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
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Account\Customer;
use App\Services\Account\AccountEditService;
use App\Services\Core\LocaleService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use libphonenumber\PhoneNumberFormat as libPhoneNumberFormat;
use Propaganistas\LaravelPhone\Exceptions\NumberParseException;
use Propaganistas\LaravelPhone\PhoneNumber;

class RegisteredUserController extends Controller
{
    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        $rules = AccountEditService::rules($request->country ?? '', true, true);
        if (setting('register_toslink')) {
            $rules['accept_tos'] = ['accepted'];
        }
        $data = $request->all();
        $data['email'] = strtolower($request->email);
        $data['phone'] = $this->formatPhone($request->phone, $request->country);
        $validator = \Validator::make($data, $rules);
        if ($validator->fails()) {
            if ($request->has('redirect')) {
                return redirect($request->get('redirect'))->withErrors($validator)->withInput();
            }

            return back()->withErrors($validator)->withInput();
        }

        if (setting('allow_registration', true) === false) {
            return back()->with('error', __('auth.register.error_registration_disabled'));
        }
        $bannedEmails = collect(explode(',', setting('banned_emails', '')))->map(function ($email) {
            return trim($email);
        });
        if ($bannedEmails->contains($request->email) || $bannedEmails->contains(explode('@', $request->email)[1] ?? '')) {
            return back()->with('error', __('auth.register.error_banned_email'));
        }
        $user = Customer::create([
            'email' => strtolower($request->email),
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
            'address' => $request->address,
            'address2' => $request->address2,
            'city' => $request->city,
            'zipcode' => $request->zipcode,
            'region' => $request->region,
            'phone' => $request->phone,
            'country' => $request->country,
            'password' => Hash::make($request->password),
            'locale' => LocaleService::fetchCurrentLocale(),
            'dark_mode' => is_darkmode(),
        ]);

        if (setting('auto_confirm_registration', false) === true) {
            $user->markEmailAsVerified();
        }
        event(new Registered($user));

        if ($request->wantsJson()) {
            return response()->noContent();
        }
        if ($request->has('origin') && $request->get('origin') != null) {
            $user->attachMetadata('origin_url', str_replace(url('/'), '', $request->get('origin')));
        }
        Auth::login($user);
        if (setting('auto_confirm_registration', false) === true) {
            return redirect()->route('front.client.index')->with('success', __('auth.register.success'));
        } else {
            return redirect()->route('front.client.onboarding');
        }

    }

    private function formatPhone(?string $phone = null, ?string $country = null): ?string
    {
        try {
            if ($phone === null || $phone === '') {
                return null;
            }

            return (new PhoneNumber($phone, $country))->format(libPhoneNumberFormat::E164);
        } catch (NumberParseException $e) {
            return 'invalid';
        }
    }
}
