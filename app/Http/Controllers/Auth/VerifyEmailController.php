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


namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Account\Customer;
use App\Models\ActionLog;
use App\Models\Store\Basket\Basket;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request)
    {
        $basket = Basket::getBasket();
        $redirect = route('front.client.index').'?verified=1';
        $translate = 'auth.verified.continue';
        if ($basket && $basket->items()->count() != 0) {
            $redirect = route('front.store.basket.checkout');
            $translate = 'auth.verified.continue_order';
        }
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(
                $redirect
            );
        }

        if ($request->user()->markEmailAsVerified()) {
            \Auth::login($request->user());
            \Session::forget('warning');
            event(new Verified($request->user()));
            ActionLog::log(ActionLog::ACCOUNT_VERIFIED, Customer::class, $request->user()->id, null, $request->user()->id);
        }

        return view('front.auth.confirmed', compact('translate', 'redirect'));
    }
}
