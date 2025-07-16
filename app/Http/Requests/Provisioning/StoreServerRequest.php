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
namespace App\Http\Requests\Provisioning;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @OA\Schema(
 *     schema="StoreServerRequest",
 *     title="Provisioning Server Store",
 *     description="Fields required to create a new provisioning server.",
 *     required={"name", "hostname", "address", "type"},
 *
 *     @OA\Property(property="name", type="string", example="Node-Paris-01"),
 *     @OA\Property(property="ip", type="string", example="192.168.0.10"),
 *     @OA\Property(property="port", type="integer", example=443, description="Port used to connect to the server"),
 *     @OA\Property(property="username", type="string", example="root", description="Server login username"),
 *     @OA\Property(property="password", type="string", example="securepassword", description="Server login password"),
 *     @OA\Property(property="hostname", type="string", example="paris01.clientx.local", description="The hostname of the server"),
 *     @OA\Property(property="address", type="string", example="node1.clientx.local", description="The provisioning connection address"),
 *     @OA\Property(property="type", type="string", example="pterodactyl", description="Type of server (e.g., pterodactyl, proxmox)"),
 *     @OA\Property(property="status", type="string", enum={"active", "hidden", "unreferenced"}, example="active", description="Status of the server"),
 *     @OA\Property(property="maxaccounts", type="integer", nullable=true, example=50, description="Maximum number of services allowed on this server")
 * )
 */
class StoreServerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $types = app('extension')->getProductTypes()->filter(function ($k) {
            return $k->server() != null;
        })->map(function ($k) {
            return $k->uuid();
        })->merge(['none']);

        return [
            'name' => ['string', 'max:255', Rule::unique('servers', 'name')->ignore($this->id)],
            'ip' => ['string', 'max:255', Rule::unique('servers', 'ip')->ignore($this->id)],
            'port' => ['numeric', 'min:1', 'max:65535'],
            'username' => ['string'],
            'password' => ['string'],
            'status' => ['string', Rule::in(['active', 'hidden', 'unreferenced'])],
            'type' => ['string', Rule::in($types)],
            'hostname' => ['string', 'required'],
            'address' => ['string', 'required'],
            'maxaccounts' => ['numeric', 'min:0', 'nullable'],
        ];
    }
}
