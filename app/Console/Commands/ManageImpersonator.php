<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * Bootstrap kebenaran impersonate melalui CLI (perlu akses pelayan).
 * Selepas ada impersonator pertama, dia boleh beri/tarik balik untuk
 * pengguna lain di Settings → Pengguna. Pengguna dicari ikut users.id.
 *
 *   php artisan user:impersonator --all          # cari ID pengguna
 *   php artisan user:impersonator 5
 *   php artisan user:impersonator 5 --revoke
 *   php artisan user:impersonator --list
 *   php artisan user:impersonator --revoke-all   # kill switch
 */
class ManageImpersonator extends Command
{
    protected $signature = 'user:impersonator
                            {id? : ID pengguna (users.id)}
                            {--revoke : Tarik balik kebenaran}
                            {--list : Senarai pengguna yang boleh impersonate}
                            {--all : Senarai semua pengguna beserta ID}
                            {--revoke-all : Tarik balik kebenaran SEMUA impersonator (kill switch)}';

    protected $description = 'Beri atau tarik balik kebenaran impersonate kepada pengguna (ikut ID)';

    public function handle(): int
    {
        if ($this->option('list') || $this->option('all')) {
            $rows = User::query()
                ->when($this->option('list'), fn ($q) => $q->where('can_impersonate', true))
                ->orderBy('id')
                ->get(['id', 'name', 'email', 'role', 'status', 'can_impersonate'])
                ->map(fn ($u) => [$u->id, $u->name, $u->email, $u->role, $u->status, $u->can_impersonate ? 'Ya' : '-'])
                ->all();
            $rows ? $this->table(['ID', 'Nama', 'E-mel', 'Peranan', 'Status', 'Impersonator'], $rows)
                  : $this->info('Tiada pengguna dijumpai.');

            return self::SUCCESS;
        }

        if ($this->option('revoke-all')) {
            $users = User::where('can_impersonate', true)->get();
            foreach ($users as $user) {
                $user->forceFill(['can_impersonate' => false])->save();
                activity('User')->performedOn($user)
                    ->withProperties(['can_impersonate' => false, 'via' => 'artisan', 'revoke_all' => true])
                    ->log('impersonate.revoke');
            }
            $this->info("Kebenaran impersonate ditarik balik untuk {$users->count()} pengguna.");

            return self::SUCCESS;
        }

        $id = $this->argument('id');
        if (! $id || ! ctype_digit((string) $id)) {
            $this->error('Sila nyatakan ID pengguna (nombor), atau guna --all / --list / --revoke-all.');

            return self::INVALID;
        }

        $user = User::find($id);
        if (! $user) {
            $this->error("Tiada pengguna dengan ID {$id}. Guna --all untuk lihat senarai.");

            return self::FAILURE;
        }

        $grant = ! $this->option('revoke');
        if ($grant && $user->status !== 'active') {
            $this->error("{$user->name} tidak aktif.");

            return self::FAILURE;
        }

        $user->forceFill(['can_impersonate' => $grant])->save();

        activity('User')->performedOn($user)
            ->withProperties(['can_impersonate' => $grant, 'via' => 'artisan'])
            ->log($grant ? 'impersonate.grant' : 'impersonate.revoke');

        $this->info($grant
            ? "#{$user->id} {$user->name} <{$user->email}> kini BOLEH impersonate."
            : "Kebenaran impersonate #{$user->id} {$user->name} <{$user->email}> ditarik balik.");

        return self::SUCCESS;
    }
}
