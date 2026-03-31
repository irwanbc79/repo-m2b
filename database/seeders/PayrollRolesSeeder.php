<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Assigns HRD/Payroll roles to existing users.
 *
 * Uses the project's native JSON-based role system (users.roles column).
 * No Spatie package required.
 *
 * Roles assigned:
 *  - admin   → user already marked as admin in the system
 *  - director → Eka Mayang Sari Harahap
 *  - finance  → Nurul Asyikin
 */
class PayrollRolesSeeder extends Seeder
{
    public function run(): void
    {
        // ---- Director: Eka Mayang Sari Harahap ----
        $director = User::where('name', 'like', '%Eka Mayang%')->first();
        if ($director) {
            $this->addRole($director, 'director');
            $this->command->info("Director role assigned to: {$director->name}");
        } else {
            $this->command->warn('Director user (Eka Mayang) not found — skipped.');
        }

        // ---- Finance: Nurul Asyikin ----
        $finance = User::where('name', 'like', '%Nurul Asyikin%')->first();
        if ($finance) {
            $this->addRole($finance, 'finance');
            $this->command->info("Finance role assigned to: {$finance->name}");
        } else {
            $this->command->warn('Finance user (Nurul Asyikin) not found — skipped.');
        }

        // ---- Admin: first user with existing admin role/role column ----
        $admin = User::where('role', 'admin')
            ->orWhere('roles', 'like', '%"admin"%')
            ->first();
        if ($admin) {
            $this->addRole($admin, 'admin');
            $this->command->info("Admin role confirmed for: {$admin->name}");
        } else {
            $this->command->warn('Admin user not found — skipped.');
        }

        $this->command->info('PayrollRolesSeeder completed.');
    }

    /**
     * Add a role to a user's JSON roles array without removing existing roles.
     */
    private function addRole(User $user, string $role): void
    {
        $roles = $user->roles ?? [];

        // Fallback: if roles is empty/null but legacy role column exists, migrate it
        if (empty($roles) && $user->role) {
            $roles = [$user->role];
        }

        if (!in_array($role, $roles)) {
            $roles[] = $role;
        }

        // Update both columns for compatibility
        DB::table('users')->where('id', $user->id)->update([
            'roles' => json_encode(array_values($roles)),
            'role'  => $roles[0], // keep primary role in legacy column
        ]);
    }
}
