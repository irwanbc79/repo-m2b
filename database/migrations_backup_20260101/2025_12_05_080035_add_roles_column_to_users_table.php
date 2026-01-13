<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // 1. Tambah kolom 'roles' (bisa menampung banyak data / JSON)
        if (!Schema::hasColumn('users', 'roles')) {
            Schema::table('users', function (Blueprint $table) {
                $table->text('roles')->nullable()->after('email'); 
            });
        }

        // 2. MIGRASI DATA: Pindahkan data dari kolom 'role' lama ke 'roles' baru
        $users = DB::table('users')->get();
        foreach ($users as $user) {
            // Jika punya role lama, tapi roles baru masih kosong
            if (isset($user->role) && empty($user->roles)) {
                // Ubah "admin" menjadi format array ["admin"]
                $rolesArray = json_encode([$user->role]);
                
                DB::table('users')->where('id', $user->id)->update([
                    'roles' => $rolesArray
                ]);
            }
        }

        // 3. (Opsional) Kita biarkan kolom 'role' lama dulu sebagai cadangan
        // Schema::table('users', function (Blueprint $table) {
        //    $table->dropColumn('role');
        // });
    }

    public function down()
    {
        // Rollback jika perlu
    }
};