<?php

/*
 * This file is part of the CLIENTXCMS project.
 * This is the Client API AuthController.
 *
 * Year: 2025
 */

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\Account\Customer;
use App\Services\Account\AccountEditService;
use App\Services\Core\LocaleService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use libphonenumber\PhoneNumberFormat as libPhoneNumberFormat;
use Propaganistas\LaravelPhone\Exceptions\NumberParseException;
use Propaganistas\LaravelPhone\PhoneNumber;

/**
 * @OA\Tag(
 *     name="Authentication",
 *     description="Authentication endpoints for client API"
 * )
 */
class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/client/auth/login",
     *     summary="Login to client account",
     *     tags={"Authentication"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password123")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Login successful",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="token", type="string"),
     *             @OA\Property(property="token_type", type="string", example="Bearer"),
     *             @OA\Property(property="requires_2fa", type="boolean"),
     *             @OA\Property(property="customer", type="object")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Invalid credentials"),
     *     @OA\Response(response=403, description="Account banned or disabled")
     * )
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $customer = Customer::where('email', strtolower($request->email))->first();

        if (! $customer || ! Hash::check($request->password, $customer->password)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        // Check if customer is banned
        if ($customer->isBanned()) {
            $ban = $customer->getBlockedMessage();
            throw ValidationException::withMessages([
                'email' => [$ban],
            ]);
        }

        // Check if 2FA is required
        if ($customer->twoFactorEnabled()) {
            // Create a temporary token for 2FA verification
            $tempToken = $customer->createToken('2fa-pending', ['2fa:pending'], now()->addMinutes(5));

            return response()->json([
                'requires_2fa' => true,
                'token' => $tempToken->plainTextToken,
                'token_type' => 'Bearer',
                'message' => __('auth.2fa.required'),
            ]);
        }

        // Create full access token
        $token = $customer->createToken('client-api', ['*']);

        return response()->json([
            'requires_2fa' => false,
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'customer' => [
                'id' => $customer->id,
                'email' => $customer->email,
                'firstname' => $customer->firstname,
                'lastname' => $customer->lastname,
            ],
        ]);
    }

    /**
     * @OA\Post(
     *     path="/client/auth/2fa/verify",
     *     summary="Verify 2FA code after login",
     *     tags={"Authentication"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"code"},
     *
     *             @OA\Property(property="code", type="string", example="123456")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="2FA verification successful",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="token", type="string"),
     *             @OA\Property(property="token_type", type="string", example="Bearer")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Invalid 2FA code")
     * )
     */
    public function verify2fa(Request $request): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $customer = $request->user();

        if (! $customer) {
            return response()->json(['error' => __('auth.unauthenticated')], 401);
        }

        $google2fa = new \PragmaRX\Google2FA\Google2FA;
        $valid = $google2fa->verifyKey($customer->two_factor_secret, $request->code);

        // Also check recovery codes
        if (! $valid) {
            $recoveryCodes = $customer->twoFactorRecoveryCodes();
            if (in_array($request->code, $recoveryCodes)) {
                $valid = true;
                // Remove used recovery code
                $customer->update([
                    'two_factor_recovery_codes' => encrypt(
                        json_encode(array_values(array_filter($recoveryCodes, fn ($code) => $code !== $request->code)))
                    ),
                ]);
            }
        }

        if (! $valid) {
            throw ValidationException::withMessages([
                'code' => [__('auth.2fa.invalid')],
            ]);
        }

        // Revoke the temporary token and create a full access token
        $request->user()->currentAccessToken()->delete();
        $token = $customer->createToken('client-api', ['*']);

        return response()->json([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'customer' => [
                'id' => $customer->id,
                'email' => $customer->email,
                'firstname' => $customer->firstname,
                'lastname' => $customer->lastname,
            ],
        ]);
    }

    /**
     * @OA\Post(
     *     path="/client/auth/register",
     *     summary="Register a new client account",
     *     tags={"Authentication"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"email", "password", "password_confirmation", "firstname", "lastname", "country"},
     *
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="password", type="string", format="password"),
     *             @OA\Property(property="password_confirmation", type="string", format="password"),
     *             @OA\Property(property="firstname", type="string"),
     *             @OA\Property(property="lastname", type="string"),
     *             @OA\Property(property="address", type="string"),
     *             @OA\Property(property="address2", type="string"),
     *             @OA\Property(property="city", type="string"),
     *             @OA\Property(property="zipcode", type="string"),
     *             @OA\Property(property="region", type="string"),
     *             @OA\Property(property="phone", type="string"),
     *             @OA\Property(property="country", type="string", example="FR")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Registration successful",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="token", type="string"),
     *             @OA\Property(property="customer", type="object")
     *         )
     *     ),
     *
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=403, description="Registration disabled")
     * )
     */
    public function register(Request $request): JsonResponse
    {
        if (setting('allow_registration', true) === false) {
            return response()->json([
                'error' => __('auth.register.error_registration_disabled'),
            ], 403);
        }

        $rules = AccountEditService::rules($request->country ?? '', true, true);
        if (setting('register_toslink')) {
            $rules['accept_tos'] = ['accepted'];
        }

        $data = $request->all();
        $data['email'] = strtolower($request->email);
        $data['phone'] = $this->formatPhone($request->phone, $request->country ?? '');

        $validator = \Validator::make($data, $rules);
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $bannedEmails = collect(explode(',', setting('banned_emails', '')))->map(fn ($email) => trim($email));
        if ($bannedEmails->contains($request->email) || $bannedEmails->contains(explode('@', $request->email)[1] ?? '')) {
            return response()->json([
                'error' => __('auth.register.error_banned_email'),
            ], 403);
        }

        $customer = Customer::create([
            'email' => strtolower($request->email),
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
            'address' => $request->address,
            'address2' => $request->address2,
            'city' => $request->city,
            'zipcode' => $request->zipcode,
            'region' => $request->region,
            'phone' => $data['phone'],
            'country' => $request->country,
            'password' => Hash::make($request->password),
            'locale' => LocaleService::fetchCurrentLocale(),
        ]);

        if (setting('auto_confirm_registration', false) === true) {
            $customer->markEmailAsVerified();
        }

        event(new Registered($customer));

        $token = $customer->createToken('client-api', ['*']);

        return response()->json([
            'message' => __('auth.register.success'),
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'customer' => [
                'id' => $customer->id,
                'email' => $customer->email,
                'firstname' => $customer->firstname,
                'lastname' => $customer->lastname,
                'email_verified' => $customer->hasVerifiedEmail(),
            ],
        ], 201);
    }

    /**
     * @OA\Post(
     *     path="/client/auth/forgot-password",
     *     summary="Request password reset link",
     *     tags={"Authentication"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"email"},
     *
     *             @OA\Property(property="email", type="string", format="email")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Password reset link sent",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        if (setting('allow_reset_password', true) === false) {
            return response()->json([
                'error' => __('auth.forgot.error_disabled'),
            ], 403);
        }

        $request->validate([
            'email' => ['required', 'email'],
        ]);

        Password::sendResetLink($request->only('email'));

        // Always return success to prevent email enumeration
        return response()->json([
            'message' => __('auth.forgot.success'),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/client/auth/reset-password",
     *     summary="Reset password with token",
     *     tags={"Authentication"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"token", "email", "password", "password_confirmation"},
     *
     *             @OA\Property(property="token", type="string"),
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="password", type="string", format="password"),
     *             @OA\Property(property="password_confirmation", type="string", format="password")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Password reset successful",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *
     *     @OA\Response(response=422, description="Invalid token or validation error")
     * )
     */
    public function resetPassword(Request $request): JsonResponse
    {
        if (setting('allow_reset_password', true) === false) {
            return response()->json([
                'error' => __('auth.forgot.error_disabled'),
            ], 403);
        }

        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return response()->json([
            'message' => __('auth.reset.success'),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/client/auth/logout",
     *     summary="Logout and revoke token",
     *     tags={"Authentication"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Logout successful",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => __('auth.logout.success'),
        ]);
    }

    private function formatPhone(?string $phone, string $country): ?string
    {
        try {
            if ($phone === null || $phone === '') {
                return null;
            }

            return (new PhoneNumber($phone, $country))->format(libPhoneNumberFormat::INTERNATIONAL);
        } catch (NumberParseException $e) {
            return 'invalid';
        }
    }
}
