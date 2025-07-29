<?php

namespace Database\Seeders\Reference;

use App\Models\Bank;
use Illuminate\Database\Seeder;

class BankSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            'Dana' => null,
            'BNI' => 'Bank Negara Indonesia',
            'BRI' => 'Bank Rakyat Indonesia',
            'BCA' => 'Bank Central Asia',
            'Mandiri' => 'Bank Mandiri',
         ] as $key => $item) {
            $bank = new Bank();
            $bank->short_name = $key;
            $bank->full_name = $item;
            $bank->save();
        }
    }
}
