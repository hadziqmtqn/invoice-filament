<?php

namespace Database\Seeders\Setting;

use App\Models\Application;
use Illuminate\Database\Seeder;

class ApplicationSeeder extends Seeder
{
    public function run(): void
    {
        $application = new Application();
        $application->name = 'Invoice App';
        $application->email = 'khadziq@bkn.my.id';
        $application->whatsapp_number = '085157088717';
        $application->save();
    }
}
