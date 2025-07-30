<?php

namespace App\Traits;

use App\Models\WhatsappConfig;

trait WhatsappConfigTrait
{
    public function whatsappConfig(): ?WhatsappConfig
    {
        return WhatsappConfig::active()
            ->first();
    }
}
