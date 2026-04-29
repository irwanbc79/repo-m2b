<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateKonsultanPajakUser extends Command
{
    protected $signature = 'konsultan:create {name} {email} {password}';

    protected $description = 'Create a new user with the konsultan_pajak role';

    public function handle(): int
    {
        $email = $this->argument('email');

        if (User::where('email', $email)->exists()) {
            $this->error("User dengan email '{$email}' sudah ada. Dibatalkan.");
            return self::FAILURE;
        }

        $user = User::create([
            'name'     => $this->argument('name'),
            'email'    => $email,
            'password' => Hash::make($this->argument('password')),
            'roles'    => ['konsultan_pajak'],
            'role'     => 'konsultan_pajak',
            'is_active' => true,
        ]);

        $this->info('User berhasil dibuat:');
        $this->table(['Field', 'Value'], [
            ['ID',      $user->id],
            ['Nama',    $user->name],
            ['Email',   $user->email],
            ['Role',    'konsultan_pajak'],
            ['Status',  'active'],
        ]);

        return self::SUCCESS;
    }
}
