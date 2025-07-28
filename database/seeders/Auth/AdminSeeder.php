<?php

namespace Database\Seeders\Auth;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = Role::create([
            'name' => 'super_admin'
        ]);

        Role::create([
            'name' => 'user'
        ]);

        $mainSuperAdmin = new User();
        $mainSuperAdmin->name = 'Super Admin';
        $mainSuperAdmin->email = 'superadmin@bkn.my.id';
        $mainSuperAdmin->password = Hash::make('superadmin');
        $mainSuperAdmin->email_verified_at = now();
        $mainSuperAdmin->save();

        $mainSuperAdmin->assignRole($superAdmin);
    }
}
