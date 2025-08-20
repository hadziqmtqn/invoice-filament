<?php

namespace Database\Seeders\Auth;

use App\Models\UserProfile;
use Illuminate\Database\Seeder;

class UserFactorySeeder extends Seeder
{
    public function run(): void
    {
        UserProfile::factory(100)
            ->create()
            ->each(function (UserProfile $profile) {
                $profile->user->assignRole('user');
            });
    }
}
