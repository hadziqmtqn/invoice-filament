<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\UserProfile;
use Database\Seeders\Auth\AdminSeeder;
use Database\Seeders\Auth\PermissionSeeder;
use Database\Seeders\Reference\ItemSeeder;
use Database\Seeders\Setting\ApplicationSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            PermissionSeeder::class,
            ApplicationSeeder::class,
            ItemSeeder::class
        ]);

        UserProfile::factory(100)
            ->create()
            ->each(function (UserProfile $profile) {
                $profile->user->assignRole('user');
            });
    }
}
