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

use App\Helpers\Countries;
use App\Http\Controllers\Controller;
use App\Models\Account\Customer;
use App\Models\ActionLog;
use App\Rules\Valid2FACodeRule;
use App\Services\Account\AccountDeletionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FAQRCode\Google2FA;

/**
 * @OA\Tag(
 *     name="Customer Profile",
 *     description="Profile management endpoints for customer API"
 * )
 */
class ProfileController extends Controller
{
    /**
     * @OA\Get(
     *     path="/client/profile",
     *     summary="Get current user profile",
     *     tags={"Customer Profile"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Profile data",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="email", type="string"),
     *                 @OA\Property(property="firstname", type="string"),
     *                 @OA\Property(property="lastname", type="string"),
     *                 @OA\Property(property="address", type="string"),
     *                 @OA\Property(property="city", type="string"),
     *                 @OA\Property(property="country", type="string"),
     *                 @OA\Property(property="phone", type="string"),
     *                 @OA\Property(property="balance", type="number"),
     *                 @OA\Property(property="two_factor_enabled", type="boolean"),
     *                 @OA\Property(property="created_at", type="string", format="date-time")
     *             )
     *         )
     *     )
     * )
     */
    public function show(Request $request): JsonResponse
    {
        $customer = $request->user();

        return response()->json([
            'data' => [
                'id' => $customer->id,
                'email' => $customer->email,
                'firstname' => $customer->firstname,
                'lastname' => $customer->lastname,
                'address' => $customer->address,
                'address2' => $customer->address2,
                'city' => $customer->city,
                'zipcode' => $customer->zipcode,
                'region' => $customer->region,
                'country' => $customer->country,
                'country_name' => Countries::getName($customer->country),
                'phone' => $customer->phone,
                'balance' => $customer->balance,
                'formatted_balance' => formatted_price($customer->balance),
                'locale' => $customer->locale,
                'email_verified' => $customer->hasVerifiedEmail(),
                'two_factor_enabled' => $customer->twoFactorEnabled(),
                'has_security_question' => $customer->security_question_id !== null,
                'created_at' => $customer->created_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * @OA\Put(
     *     path="/client/profile",
     *     summary="Update profile information",
     *     tags={"Customer Profile"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="firstname", type="string"),
     *             @OA\Property(property="lastname", type="string"),
     *             @OA\Property(property="address", type="string"),
     *             @OA\Property(property="address2", type="string"),
     *             @OA\Property(property="city", type="string"),
     *             @OA\Property(property="zipcode", type="string"),
     *             @OA\Property(property="region", type="string"),
     *             @OA\Property(property="country", type="string"),
     *             @OA\Property(property="phone", type="string")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Profile updated successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function update(Request $request): JsonResponse
    {
        $customer = $request->user();

        $validated = $request->validate([
            'firstname' => ['sometimes', 'required', 'string', 'max:100'],
            'lastname' => ['sometimes', 'required', 'string', 'max:100'],
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address2' => ['sometimes', 'nullable', 'string', 'max:255'],
            'city' => ['sometimes', 'nullable', 'string', 'max:100'],
            'zipcode' => ['sometimes', 'nullable', 'string', 'max:20'],
            'region' => ['sometimes', 'nullable', 'string', 'max:100'],
            'country' => ['sometimes', 'nullable', 'string', 'size:2'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
        ]);

        $customer->fill($validated);

        if ($customer->isDirty('email')) {
            $customer->email_verified_at = null;
        }

        $customer->save();

        return response()->json([
            'message' => __('client.profile.updated'),
            'data' => [
                'id' => $customer->id,
                'email' => $customer->email,
                'firstname' => $customer->firstname,
                'lastname' => $customer->lastname,
            ],
        ]);
    }

    /**
     * @OA\Put(
     *     path="/client/profile/password",
     *     summary="Change password",
     *     tags={"Customer Profile"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"current_password", "password", "password_confirmation"},
     *
     *             @OA\Property(property="current_password", type="string"),
     *             @OA\Property(property="password", type="string"),
     *             @OA\Property(property="password_confirmation", type="string")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Password changed successfully"
     *     )
     * )
     */
    public function password(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::defaults()],
        ]);

        $request->user()->update(['password' => Hash::make($request->password)]);

        return response()->json([
            'message' => __('client.profile.changepassword'),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/client/profile/2fa",
     *     summary="Enable or disable 2FA",
     *     tags={"Customer Profile"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"code"},
     *
     *             @OA\Property(property="code", type="string", example="123456"),
     *             @OA\Property(property="secret", type="string", description="Required when enabling 2FA")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="2FA toggled successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="enabled", type="boolean"),
     *             @OA\Property(property="recovery_codes", type="array", @OA\Items(type="string"))
     *         )
     *     )
     * )
     */
    public function toggle2fa(Request $request): JsonResponse
    {
        $customer = $request->user();

        if ($customer->twoFactorEnabled()) {
            // Disable 2FA
            $request->validate([
                'code' => ['required', 'string', 'size:6', new Valid2FACodeRule($customer->two_factor_secret)],
            ]);

            ActionLog::log(ActionLog::TWO_FACTOR_DISABLED, Customer::class, $customer->id, null, $customer->id);
            $customer->twoFactorDisable();

            return response()->json([
                'message' => __('client.profile.2fa.disabled'),
                'enabled' => false,
            ]);
        } else {
            // Enable 2FA
            $request->validate([
                'secret' => ['required', 'string'],
                'code' => ['required', 'string', 'size:6', new Valid2FACodeRule($request->secret)],
            ]);

            ActionLog::log(ActionLog::TWO_FACTOR_ENABLED, Customer::class, $customer->id, null, $customer->id);
            $customer->twoFactorEnable($request->secret);

            return response()->json([
                'message' => __('client.profile.2fa.enabled'),
                'enabled' => true,
                'recovery_codes' => $customer->twoFactorRecoveryCodes(),
            ]);
        }
    }

    /**
     * @OA\Get(
     *     path="/client/profile/2fa/setup",
     *     summary="Get 2FA setup information (secret and QR code)",
     *     tags={"Customer Profile"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="2FA setup data",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="secret", type="string"),
     *             @OA\Property(property="qr_code", type="string", description="SVG QR code")
     *         )
     *     )
     * )
     */
    public function setup2fa(Request $request): JsonResponse
    {
        $customer = $request->user();

        if ($customer->twoFactorEnabled()) {
            return response()->json([
                'error' => __('client.profile.2fa.already_enabled'),
            ], 400);
        }

        $google = new Google2FA;
        $secret = $google->generateSecretKey();
        $google->setQrcodeService(
            new \PragmaRX\Google2FAQRCode\QRCode\Bacon(
                new \BaconQrCode\Renderer\Image\SvgImageBackEnd
            )
        );

        $qrcode = $google->getQRCodeInline(
            config('app.name'),
            $customer->email,
            $secret
        );

        return response()->json([
            'secret' => $secret,
            'qr_code' => $qrcode,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/client/profile/2fa/recovery-codes",
     *     summary="Get 2FA recovery codes",
     *     tags={"Customer Profile"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Recovery codes",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="recovery_codes", type="array", @OA\Items(type="string"))
     *         )
     *     )
     * )
     */
    public function recoveryCodes(Request $request): JsonResponse
    {
        $customer = $request->user();

        if (! $customer->twoFactorEnabled()) {
            return response()->json([
                'error' => __('client.profile.2fa.not_enabled'),
            ], 400);
        }

        ActionLog::log(ActionLog::TWO_FACTOR_RECOVERY_CODES_GENERATED, Customer::class, $customer->id, null, $customer->id);

        return response()->json([
            'recovery_codes' => $customer->twoFactorRecoveryCodes(),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/client/profile/security-question",
     *     summary="Save security question",
     *     tags={"Customer Profile"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"security_question_id", "security_answer", "current_password"},
     *
     *             @OA\Property(property="security_question_id", type="integer"),
     *             @OA\Property(property="security_answer", type="string"),
     *             @OA\Property(property="current_password", type="string")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Security question saved"
     *     )
     * )
     */
    public function saveSecurityQuestion(Request $request): JsonResponse
    {
        $request->validate([
            'security_question_id' => ['required', 'exists:security_questions,id'],
            'security_answer' => ['required', 'string', 'min:2', 'max:100'],
            'current_password' => ['required', 'current_password'],
        ]);

        $request->user()->setSecurityQuestion(
            (int) $request->security_question_id,
            $request->security_answer
        );

        return response()->json([
            'message' => __('client.profile.security_question_saved'),
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/client/profile",
     *     summary="Delete account",
     *     tags={"Customer Profile"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"password"},
     *
     *             @OA\Property(property="password", type="string")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Account deleted"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Cannot delete account (active services, etc.)"
     *     )
     * )
     */
    public function deleteAccount(Request $request): JsonResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $customer = $request->user();
        $deletionService = new AccountDeletionService;

        try {
            $deletionService->delete($customer);

            return response()->json([
                'message' => __('client.profile.delete.success'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}
