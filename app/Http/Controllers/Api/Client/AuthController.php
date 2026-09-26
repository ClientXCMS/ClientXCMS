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

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\Account\Customer;
use App\Services\Account\AccountEditService;
use App\Services\Auth\DummyPasswordHash;
use App\Services\Auth\MfaConfig;
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
use Laravel\Sanctum\PersonalAccessToken;
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
     *         description="Full access token, or a pending token (valid only on the 2FA routes) when requires_2fa is true",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="token", type="string"),
     *             @OA\Property(property="token_type", type="string", example="Bearer"),
     *             @OA\Property(property="requires_2fa", type="boolean"),
     *             @OA\Property(property="second_factor", type="string", enum={"totp", "email", "totp_email"}, description="Present when requires_2fa is true"),
     *             @OA\Property(property="customer", type="object")
     *         )
     *     ),
     *
     *     @OA\Response(response=422, description="Invalid credentials or banned account"),
     *     @OA\Response(response=429, description="Too many attempts")
     * )
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $customer = Customer::where('email', strtolower($request->email))->first();
        $passwordMatches = Hash::check($request->password, $customer?->password ?? DummyPasswordHash::get());

        if (! $customer || ! $passwordMatches) {
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

        $needs = $customer->twoFactorRequirements('web', $request->ip());
        if ($needs['totp']) {
            return $this->pendingSecondFactorResponse($customer, ['2fa:pending'], $needs['email'] ? 'totp_email' : 'totp');
        }
        if ($needs['email']) {
            $customer->sendTwoFactorEmailCode('web', $request->ip());

            return $this->pendingSecondFactorResponse($customer, ['2fa:pending'], 'email');
        }

        return $this->fullAccessResponse($customer, ['requires_2fa' => false]);
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
     *         description="Either a full access token, or a new pending token when the email code is still expected",
     *
     *         @OA\JsonContent(
     *             oneOf={
     *
     *                 @OA\Schema(
     *
     *                     @OA\Property(property="token", type="string"),
     *                     @OA\Property(property="token_type", type="string", example="Bearer"),
     *                     @OA\Property(property="customer", type="object")
     *                 ),
     *
     *                 @OA\Schema(
     *
     *                     @OA\Property(property="requires_2fa", type="boolean", example=true),
     *                     @OA\Property(property="second_factor", type="string", enum={"email"}),
     *                     @OA\Property(property="token", type="string", description="Pending token, only valid on the 2FA routes"),
     *                     @OA\Property(property="token_type", type="string", example="Bearer"),
     *                     @OA\Property(property="message", type="string")
     *                 )
     *             }
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Not authenticated with a pending 2FA token"),
     *     @OA\Response(response=403, description="Token is not a pending 2FA token"),
     *     @OA\Response(response=422, description="Invalid 2FA code"),
     *     @OA\Response(response=429, description="Too many attempts")
     * )
     */
    public function verify2fa(Request $request): JsonResponse
    {
        if (! $this->hasPersonalAccessToken($request)) {
            return response()->json(['error' => __('auth.unauthenticated')], 401);
        }

        $request->validate([
            'code' => ['required', 'string', 'max:64', new \App\Rules\Valid2FACodeInput],
        ]);

        $customer = $request->user();

        $needs = $customer->twoFactorRequirements('web', $request->ip());
        $pendingToken = $customer->currentAccessToken();

        if ($needs['totp'] && ! $customer->tokenCan('2fa:totp-done')) {
            $this->assertSecondFactor($customer->verifyDeviceFactor($request->code));
            $pendingToken->delete();
            if (! $needs['email']) {
                return $this->fullAccessResponse($customer);
            }
            $customer->sendTwoFactorEmailCode('web', $request->ip());

            return $this->pendingSecondFactorResponse($customer, ['2fa:pending', '2fa:totp-done'], 'email');
        }

        $this->assertSecondFactor($needs['email'] && $customer->isValidEmailTwoFactorCode(str_replace(' ', '', $request->code)));
        $pendingToken->delete();

        return $this->fullAccessResponse($customer);
    }

    /**
     * @OA\Post(
     *     path="/client/auth/2fa/email",
     *     summary="Send the email verification code again",
     *     tags={"Authentication"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Code sent, or still valid from a previous request",
     *
     *         @OA\JsonContent(@OA\Property(property="message", type="string"))
     *     ),
     *
     *     @OA\Response(response=401, description="Not authenticated with a pending 2FA token"),
     *     @OA\Response(response=403, description="Token is not a pending 2FA token"),
     *     @OA\Response(response=409, description="No email code expected at this step", @OA\JsonContent(@OA\Property(property="error", type="string"))),
     *     @OA\Response(response=429, description="Too many requests or email code on cooldown", @OA\JsonContent(@OA\Property(property="error", type="string")))
     * )
     */
    public function sendTwoFactorEmailCode(Request $request): JsonResponse
    {
        if (! $this->hasPersonalAccessToken($request)) {
            return response()->json(['error' => __('auth.unauthenticated')], 401);
        }

        $customer = $request->user();
        $needs = $customer->twoFactorRequirements('web', $request->ip());

        if (! $needs['email'] || ($needs['totp'] && ! $customer->tokenCan('2fa:totp-done'))) {
            return response()->json(['error' => __('auth.2fa.email_not_expected')], 409);
        }

        if ($customer->isEmailTwoFactorOnCooldown()) {
            return response()->json(['error' => __('client.profile.2fa.cooldown_active', [
                'minutes' => MfaConfig::emailCooldownMinutes(),
            ])], 429);
        }

        $customer->sendTwoFactorEmailCode('web', $request->ip());

        return response()->json(['message' => __('client.profile.2fa.email_sent')]);
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

        $token = $customer->createToken('client-api', ['client-api']);

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
                $user->tokens()->delete();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__('passwords.token')],
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

    // A first-party session gets a TransientToken whose can() is always true, which would skip the TOTP step.
    private function hasPersonalAccessToken(Request $request): bool
    {
        return $request->user()?->currentAccessToken() instanceof PersonalAccessToken;
    }

    private function assertSecondFactor(bool $valid): void
    {
        if (! $valid) {
            throw ValidationException::withMessages([
                'code' => [__('auth.2fa.invalid')],
            ]);
        }
    }

    private function pendingSecondFactorResponse(Customer $customer, array $abilities, string $secondFactor): JsonResponse
    {
        $token = $customer->createToken('2fa-pending', $abilities, now()->addMinutes(5));

        return response()->json([
            'requires_2fa' => true,
            'second_factor' => $secondFactor,
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'message' => __('auth.2fa.required'),
        ]);
    }

    private function fullAccessResponse(Customer $customer, array $extra = []): JsonResponse
    {
        $token = $customer->createToken('client-api', ['client-api']);

        return response()->json($extra + [
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
