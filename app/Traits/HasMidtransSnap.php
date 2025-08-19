<?php

namespace App\Traits;

use Exception;
use Midtrans\Snap;
use Midtrans\Config;

trait HasMidtransSnap
{
    /**
     * @throws Exception
     */
    public function generateMidtransSnapToken(array $params): string
    {
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = true;
        Config::$is3ds = true;

        return Snap::getSnapToken($params);
    }
}