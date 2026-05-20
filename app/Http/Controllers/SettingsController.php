<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\AuditLog;
use App\Models\SecurityPolicy;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function index(Request $request)
    {
        $users = User::orderBy('name')->get(['id', 'name', 'email', 'role', 'mmc_number', 'mfa_enabled', 'status']);

        $policies = SecurityPolicy::orderBy('id')->get(['id', 'key', 'label', 'enabled']);

        $auditLogs = AuditLog::with('user:id,name')
            ->orderByDesc('created_at')
            ->paginate(20)
            ->through(fn ($r) => [
                'id'         => $r->id,
                'ts'         => $r->created_at->format('d/m H:i:s'),
                'user'       => $r->user_name,
                'act'        => $r->action,
                'res'        => $r->resource,
                'ip'         => $r->ip_address,
                'ok'         => $r->success,
            ]);

        return Inertia::render('Settings', [
            'currentRoute' => 'settings',
            'users'        => $users,
            'policies'     => $policies,
            'auditLogs'    => $auditLogs,
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
