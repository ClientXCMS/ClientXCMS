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
namespace App\Http\Controllers\Api\Customers;

use App\Http\Controllers\Api\AbstractApiController;
use App\Http\Requests\Customer\StoreCustomerRequest;
use App\Http\Requests\Customer\UpdateCustomerRequest;
use App\Http\Resources\CustomerCollection;
use App\Models\Account\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class CustomerController extends AbstractApiController
{
    protected array $sorts = [
        'id',
        'email',
        'firstname',
        'lastname',
        'phone',
        'created_at',
        'updated_at',
    ];

    protected array $relations = [
        'invoices',
        'metadata',
        'services',
    ];

    protected array $filters = [
        'id',
        'email',
        'firstname',
        'lastname',
        'phone',
        'created_at',
        'updated_at',
    ];

    protected string $model = Customer::class;

    /**
     * @OA\Get(
     *     path="/application/customers",
     *     operationId="getCustomers",
     *     tags={"Customers"},
     *     summary="List customers",
     *
     *     @OA\Parameter(name="filter[email]", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="filter[firstname]", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="filter[lastname]", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="sort", in="query", description="Sort fields, e.g. sort=id or sort=-id", @OA\Schema(type="string")),
     *     @OA\Parameter(name="include", in="query", description="Include relations (invoices, metadata, services)", @OA\Schema(type="string", example="invoices,metadata")),
     *
     *     @OA\Response(response=200, description="List of customers")
     * )
     */
    public function index(Request $request)
    {
        return new CustomerCollection($this->queryIndex($request));
    }

    /**
     * @OA\Post(
     *     path="/application/customers",
     *     operationId="storeCustomer",
     *     tags={"Customers"},
     *     summary="Create customer",
     *
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/StoreCustomerRequest")),
     *
     *     @OA\Response(response=201, description="Customer created")
     * )
     */
    public function store(StoreCustomerRequest $request)
    {
        $customer = $request->store();

        return response()->json($customer, 201);
    }

    /**
     * @OA\Get(
     *     path="/application/customers/{id}",
     *     operationId="getCustomer",
     *     tags={"Customers"},
     *     summary="Get customer",
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Customer found"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function show(Customer $customer)
    {
        return response()->json($customer, 200);
    }

    /**
     * @OA\Post(
     *     path="/application/customers/{id}",
     *     operationId="updateCustomer",
     *     tags={"Customers"},
     *     summary="Update customer",
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/UpdateCustomerRequest")),
     *
     *     @OA\Response(response=200, description="Customer updated")
     * )
     */
    public function update(UpdateCustomerRequest $request, Customer $customer)
    {
        $customer = $request->update($customer);

        return response()->json($customer);
    }

    /**
     * @OA\Delete(
     *     path="/application/customers/{id}",
     *     operationId="deleteCustomer",
     *     tags={"Customers"},
     *     summary="Delete customer",
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Customer deleted"),
     *     @OA\Response(response=400, description="Customer has invoices")
     * )
     */
    public function destroy(Customer $customer)
    {
        if ($customer->invoices()->count() > 0) {
            return response()->json(['error' => 'Customer has invoices'], 400);
        }
        $customer->delete();

        return response()->json($customer, 200);
    }

    /**
     * @OA\Post(
     *     path="/application/customers/{id}/confirm",
     *     operationId="confirmCustomer",
     *     tags={"Customers"},
     *     summary="Confirm email of a customer",
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Email verified"),
     *     @OA\Response(response=400, description="Already verified")
     * )
     */
    public function confirm(Customer $customer)
    {
        if ($customer->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified'], 400);
        }
        $customer->markEmailAsVerified();

        return response()->json($customer, 200);
    }

    /**
     * @OA\Post(
     *     path="/application/customers/{id}/send_password",
     *     operationId="forgotPasswordCustomer",
     *     tags={"Customers"},
     *     summary="Send password reset link",
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Link sent"),
     *     @OA\Response(response=400, description="Already verified")
     * )
     */
    public function sendForgotPassword(Customer $customer)
    {
        if ($customer->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified'], 400);
        }
        Password::broker('users')->sendResetLink(['email' => $customer->email]);

        return response()->json(['message' => 'Reset link sent'], 200);
    }

    /**
     * @OA\Post(
     *     path="/application/customers/{id}/resend_confirmation",
     *     operationId="resendConfirmationCustomer",
     *     tags={"Customers"},
     *     summary="Resend email confirmation",
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Verification link sent"),
     *     @OA\Response(response=400, description="Already verified")
     * )
     */
    public function resendConfirmation(Customer $customer)
    {
        if ($customer->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified'], 400);
        }
        $customer->sendEmailVerificationNotification();

        return response()->json(['message' => 'Verification link sent'], 200);
    }

    /**
     * @OA\Post(
     *     path="/application/customers/{id}/action/{action}",
     *     operationId="customerAction",
     *     tags={"Customers"},
     *     summary="Perform action on customer (suspend, reactivate, ban, disable2FA)",
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="action", in="path", required=true, description="Action name", @OA\Schema(type="string", enum={"suspend", "reactivate", "ban", "disable2FA"})),
     *
     *     @OA\RequestBody(
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="reason", type="string", example="Violation of terms"),
     *             @OA\Property(property="notify", type="boolean", example=true),
     *             @OA\Property(property="force", type="boolean", example=false)
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="Action performed")
     * )
     */
    public function action(Customer $customer, string $action)
    {
        switch ($action) {
            case 'suspend':
                $customer->suspend($request->reason ?? 'No reason provided', $request->force ?? false, $request->notify ?? false);
                break;
            case 'reactivate':
                $customer->reactivate($request->notify ?? false);
                break;
            case 'ban':
                $customer->ban($request->reason ?? 'No reason provided', $request->force ?? false, $request->notify ?? false);
                break;
            case 'disable2FA':
                $customer->twoFactorDisable();
                break;
            default:
                break;
        }

        return response()->json($customer, 200);
    }
}
