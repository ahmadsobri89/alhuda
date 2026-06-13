<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\AuditLog;
use App\Models\ClinicProfile;
use App\Models\LookupCategory;
use App\Models\SecurityPolicy;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function index(Request $request)
    {
        $users = User::orderBy('name')->get(['id', 'name', 'email', 'role', 'mmc_number', 'mfa_enabled', 'status']);

        $policies = SecurityPolicy::orderBy('id')->get(['id', 'key', 'label', 'enabled']);

        $perPage = (int) $request->input('per_page', 20);
        if (! in_array($perPage, [20, 50, 100], true)) {
            $perPage = 20;
        }

        $auditLogs = AuditLog::with('user:id,name')
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn ($r) => [
                'id'  => $r->id,
                'ts'  => $r->created_at->format('d/m H:i:s'),
                'user'=> $r->user_name,
                'act' => $r->action,
                'res' => $r->resource,
                'ip'  => $r->ip_address,
                'ok'  => $r->success,
            ]);

        $cp = ClinicProfile::current();

        $lookupCategories = LookupCategory::orderBy('group')->orderBy('sort_order')
            ->with(['values' => fn ($q) => $q->orderBy('sort_order')])
            ->get()
            ->map(fn ($cat) => [
                'id'             => $cat->id,
                'group'          => $cat->group,
                'slug'           => $cat->slug,
                'name_ms'        => $cat->name_ms,
                'name_en'        => $cat->name_en,
                'description_ms' => $cat->description_ms,
                'description_en' => $cat->description_en,
                'sort_order'     => $cat->sort_order,
                'values'         => $cat->values->map(fn ($v) => [
                    'id'         => $v->id,
                    'code'       => $v->code,
                    'label_ms'   => $v->label_ms,
                    'label_en'   => $v->label_en,
                    'sort_order' => $v->sort_order,
                    'is_active'  => $v->is_active,
                    'is_system'  => $v->is_system,
                ]),
            ]);

        return Inertia::render('Settings', [
            'currentRoute'     => 'settings',
            'users'            => $users,
            'policies'         => $policies,
            'auditLogs'        => $auditLogs,
            'lookupCategories' => $lookupCategories,
            'filters'          => ['per_page' => $perPage],
            'clinic'           => [
                'name'         => $cp->name,
                'tagline'      => $cp->tagline,
                'reg_number'   => $cp->reg_number,
                'ckaps_number' => $cp->ckaps_number,
                'address'    => $cp->address,
                'postcode'   => $cp->postcode,
                'city'       => $cp->city,
                'state'      => $cp->state,
                'phone'      => $cp->phone,
                'fax'        => $cp->fax,
                'email'      => $cp->email,
                'website'    => $cp->website,
                'logo_url'   => $cp->logo_url,
            ],
        ]);
    }

    public function storeUser(StoreUserRequest $request)
    {
        $data = $request->validated();
        $user = User::create($data);

        AuditLog::record('user.create', "User #{$user->id} · {$user->name} ({$user->role})");

        return back()->with('success', "Pengguna {$user->name} berjaya ditambah.");
    }

    public function updateUser(UpdateUserRequest $request, User $user)
    {
        $data = $request->validated();
        if (empty($data['password'])) {
            unset($data['password']);
        }
        $user->update($data);

        AuditLog::record('user.update', "User #{$user->id} · {$user->name}");

        return back()->with('success', "Pengguna {$user->name} berjaya dikemaskini.");
    }

    public function destroyUser(User $user)
    {
        $name = $user->name;
        $user->delete();

        AuditLog::record('user.delete', "User · {$name}");

        return back()->with('success', "Pengguna {$name} berjaya dipadam.");
    }

    public function updateClinic(Request $request)
    {
        $data = $request->validate([
            'name'       => ['required', 'string', 'max:255'],
            'tagline'    => ['nullable', 'string', 'max:255'],
            'reg_number'   => ['nullable', 'string', 'max:100'],
            'ckaps_number' => ['nullable', 'string', 'max:100'],
            'address'    => ['required', 'string', 'max:500'],
            'postcode'   => ['required', 'string', 'max:10'],
            'city'       => ['required', 'string', 'max:100'],
            'state'      => ['required', 'string', 'max:100'],
            'phone'      => ['required', 'string', 'max:30'],
            'fax'        => ['nullable', 'string', 'max:30'],
            'email'      => ['nullable', 'email', 'max:255'],
            'website'    => ['nullable', 'string', 'max:255'],
            'logo'       => ['nullable', 'image', 'max:2048'],
        ]);

        $cp = ClinicProfile::firstOrCreate(['id' => 1]);

        if ($request->hasFile('logo')) {
            if ($cp->logo_path) {
                Storage::disk('public')->delete($cp->logo_path);
            }
            $data['logo_path'] = $request->file('logo')->store('clinic', 'public');
        }

        unset($data['logo']);
        $cp->update($data);

        AuditLog::record('settings.clinic.update', "Profil klinik dikemaskini: {$cp->name}");

        return back()->with('success', 'Profil klinik berjaya dikemaskini.');
    }

    public function updatePolicies(Request $request)
    {
        $policies = $request->validate([
            'policies'         => ['required', 'array'],
            'policies.*.id'    => ['required', 'integer', 'exists:security_policies,id'],
            'policies.*.enabled' => ['required', 'boolean'],
        ])['policies'];

        foreach ($policies as $p) {
            SecurityPolicy::where('id', $p['id'])->update(['enabled' => $p['enabled']]);
        }

        AuditLog::record('settings.policies.update', 'Security policies updated');

        return back()->with('success', 'Dasar keselamatan berjaya dikemaskini.');
    }
}
