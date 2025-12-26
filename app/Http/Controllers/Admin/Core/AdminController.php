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


namespace App\Http\Controllers\Admin\Core;

use App\Events\Resources\ResourceUpdatedEvent;
use App\Http\Controllers\Admin\AbstractCrudController;
use App\Http\Requests\Admin\Staff\StoreStaffRequest;
use App\Http\Requests\Admin\Staff\UpdateStaffRequest;
use App\Models\ActionLog;
use App\Models\Admin\Admin;
use App\Models\Admin\Role;
use App\Models\Admin\SecurityQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;
use PragmaRX\Google2FAQRCode\Google2FA;

class AdminController extends AbstractCrudController
{
    protected string $viewPath = 'admin.core.admins';

    protected string $routePath = 'admin.staffs';

    protected string $translatePrefix = 'admin.admins';

    protected string $model = Admin::class;

    protected int $perPage = 25;

    protected string $searchField = 'email';

    protected ?string $managedPermission = 'admin.manage_staff';

    public function getCreateParams()
    {
        $params = parent::getCreateParams();
        $params['roles'] = Role::all()->pluck('name', 'id');
        $params['locales'] = \App\Services\Core\LocaleService::getLocalesNames();

        return $params;
    }

    public function show(Admin $staff)
    {
        $this->checkPermission('show', $staff);
        $params['item'] = $staff;
        $params['roles'] = Role::all()->pluck('name', 'id');
        $params['logs'] = $staff->getLogsAction([ActionLog::NEW_LOGIN, ActionLog::FAILED_LOGIN])->paginate(10);
        $params['locales'] = \App\Services\Core\LocaleService::getLocalesNames();

        return $this->showView($params);
    }

    public function update(UpdateStaffRequest $request, Admin $staff)
    {
        $this->checkPermission('create', $staff);
        $validated = $request->validated();
        if ($request->password != null) {
            $validated['password'] = bcrypt($request->password);
        } else {
            unset($validated['password']);
        }
        if ($role = Role::find($request->role_id)) {
            if ($role->level > auth('admin')->user()->role->level) {
                return back()->with('error', __('admin.roles.error_update'));
            }
        }

        if ($request->remove_expires_at) {
            $validated['expires_at'] = null;
        }
        $staff->update($validated);

        return $this->updateRedirect($staff);
    }

    public function store(StoreStaffRequest $request)
    {
        $this->checkPermission('create');
        $validated = $request->validated();
        if ($request->password == null) {
            $validated['password'] = \Str::uuid();
        }
        $validated['password'] = bcrypt($validated['password']);

        if ($role = Role::find($request->role_id)) {
            if ($role->level > auth('admin')->user()->role->level) {
                return back()->with('error', __('admin.roles.error_update'));
            }
        }
        $staff = Admin::create($validated);
        if ($request->password == null) {
            Password::broker('admins')->sendResetLink($request->only('email'));
        }

        return $this->storeRedirect($staff);
    }

    public function destroy(Admin $staff)
    {
        $this->checkPermission('delete', $staff);
        $staff->delete();

        return $this->deleteRedirect($staff);
    }

    public function profile(Request $request)
    {
        if (! $request->user('admin')->twoFactorEnabled()) {
            $google = new Google2FA;
            $secret = $request->session()->get('2fa_secret_admin', $google->generateSecretKey());
            $google->setQrcodeService(
                new \PragmaRX\Google2FAQRCode\QRCode\Bacon(
                    new \BaconQrCode\Renderer\Image\SvgImageBackEnd
                )
            );

            $qrcode = $google->getQRCodeInline(
                config('app.name'),
                $request->user('admin')->email . ' (Admin)',
                $secret
            );
            $request->session()->put('2fa_secret_admin', $secret);
        } else {
            $qrcode = null;
        }
        $params['item'] = auth('admin')->user();
        $params['viewPath'] = $this->viewPath;
        $params['routePath'] = $this->routePath;
        $params['translatePrefix'] = 'admin.profile';
        $params['qrcode'] = $qrcode;
        $params['code'] = $request->session()->get('2fa_secret');
        $params['logs'] = $request->user('admin')->getLogsAction([ActionLog::NEW_LOGIN, ActionLog::FAILED_LOGIN])->paginate(10);
        $params['locales'] = \App\Services\Core\LocaleService::getLocalesNames();
        $params['securityQuestions'] = SecurityQuestion::getActiveQuestionsForSelect();
        $params['securityQuestionsEnabled'] = SecurityQuestion::isFeatureEnabled();

        return view($this->viewPath . '.profile', $params);
    }

    public function save2fa(Request $request)
    {
        $request->validate([
            '2fa' => ['required', 'string', 'size:6', new \App\Rules\Valid2FACodeRule($request->session()->get('2fa_secret_admin'))],
        ]);
        if ($request->user('admin')->twoFactorEnabled()) {
            $request->user('admin')->twoFactorDisable();

            return back()->with('success', __('client.profile.2fa.disabled'));
        }
        $request->user('admin')->twoFactorEnable($request->session()->get('2fa_secret_admin'));

        return back()->with('success', __('client.profile.2fa.enabled'));
    }

    public function downloadCodes()
    {
        $codes = \Auth::guard('admin')->user()->twoFactorRecoveryCodes();

        return response()->streamDownload(function () use ($codes) {
            $codes = collect($codes)->map(function ($code) {
                return $code;
            });
            echo $codes->join("\n");
        }, '2fa_recovery_codes_' . \Str::slug(config('app.name')) . '.txt');
    }

    public function updateProfile(Request $request)
    {
        $validated = $request->validate([
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('admins', 'email')->ignore(auth('admin')->user()->id)],
            'password' => 'nullable|string|min:8',
            'username' => 'nullable|string|max:255',
            'signature' => 'nullable|string|max:255',
            'locale' => ['required', 'string', Rule::in(array_keys(\App\Services\Core\LocaleService::getLocalesNames()))],
        ]);
        if ($request->password != null) {
            $validated['password'] = bcrypt($request->password);
        } else {
            unset($validated['password']);
        }
        $request->user('admin')->update($validated);
        event(new ResourceUpdatedEvent($request->user('admin')));

        return back()->with('success', __('client.profile.updated'));
    }

    public function saveSecurityQuestion(Request $request)
    {
        $admin = $request->user('admin');

        // Si l'admin veut supprimer sa question de sécurité
        if ($request->has('remove_security_question')) {
            $admin->resetSecurityQuestion();
            return back()->with('success', __('client.profile.security_question_removed'));
        }

        $request->validate([
            'security_question_id' => 'required|exists:security_questions,id',
            'security_answer' => 'required|string|min:2|max:255',
        ]);

        $admin->setSecurityQuestion(
            $request->security_question_id,
            $request->security_answer
        );

        return back()->with('success', __('client.profile.security_question_saved'));
    }
}
