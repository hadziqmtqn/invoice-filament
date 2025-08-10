<?php

namespace Database\Seeders\Setting;

use App\Models\WhatsappConfig;
use Illuminate\Database\Seeder;

class WhatsappConfigSeeder extends Seeder
{
    public function run(): void
    {
        $whatsappConfig = new WhatsappConfig();
        $whatsappConfig->provider = 'fonnte';
        $whatsappConfig->api_domain = 'https://api.fonnte.com/send';
        $whatsappConfig->api_key = 'f7wxELuZJc1VE9e7Yq7F';
        $whatsappConfig->save();
    }
}
