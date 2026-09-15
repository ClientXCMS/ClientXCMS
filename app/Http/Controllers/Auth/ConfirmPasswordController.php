<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ConfirmPasswordController extends Controller
{
    public function show()
    {
        return view('front.auth.confirm-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['password' => ['required', 'string']]);

        if (! Hash::check($request->string('password')->toString(), $request->user('web')->password)) {
            throw ValidationException::withMessages(['password' => __('auth.password')]);
        }

        $request->session()->passwordConfirmed();

        return redirect()->intended(route('front.profile.index'));
    }
}
