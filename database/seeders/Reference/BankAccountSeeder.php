<?php

namespace Database\Seeders\Reference;

use App\Models\BankAccount;
use Illuminate\Database\Seeder;
use League\Csv\Exception;
use League\Csv\InvalidArgument;
use League\Csv\Reader;
use League\Csv\UnavailableStream;

class BankAccountSeeder extends Seeder
{
    /**
     * @throws UnavailableStream
     * @throws InvalidArgument
     * @throws Exception
     */
    public function run(): void
    {
        $rows = Reader::createFromPath(database_path('import/bank-account.csv'))
            ->setDelimiter(';')
            ->setHeaderOffset(0);

        foreach ($rows as $row) {
            $bankAccount = new BankAccount();
            $bankAccount->bank_id = $row['bank_id'];
            $bankAccount->account_number = $row['account_number'];
            $bankAccount->account_name = $row['account_name'];
            $bankAccount->save();
        }
    }
}
