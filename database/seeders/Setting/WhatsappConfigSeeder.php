<?php

namespace Database\Seeders\Setting;

use App\Models\WhatsappConfig;
use Illuminate\Database\Seeder;

class WhatsappConfigSeeder extends Seeder
{
    public function run(): void
    {
        $whatsappConfig = new WhatsappConfig();
        $whatsappConfig->provider = 'wanesia';
        $whatsappConfig->api_domain = 'https://wanesia.com/api/send_message';
        $whatsappConfig->api_key = 'meHmDb9zd7hcmukcz3jHNydADKqd2Wq6kbGFMjhPYDLBACKArn';
        $whatsappConfig->save();
    }
}
