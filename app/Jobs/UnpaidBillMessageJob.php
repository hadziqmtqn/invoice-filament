<?php

namespace App\Jobs;

use App\Models\BankAccount;
use App\Traits\SendMessageTrait;
use App\Traits\WhatsappConfigTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Log;

class UnpaidBillMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, WhatsappConfigTrait, SendMessageTrait;

    protected array $data;

    /**
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function handle(): void
    {
        $bankAccounts = BankAccount::with('bank:id,short_name')
            ->active()
            ->get();

        $placeholders = [
            '[Nama]' => $this->data['user_name'],
            '[Jenis Tagihan]' => $this->data['invoice_name'],
            '[Jumlah]' => $this->data['amount'],
            '[Tanggal]' => $this->data['date'],
            '[Nomor Rekening / Metode Pembayaran]' => "\n\n" . implode("\n", $bankAccounts->map(function ($account) {
                return $account->bank?->short_name . " - " . $account->account_number . " (" . $account->account_name . ")";
            })->toArray()) . "\n\n",
        ];

        $messageTemplate = $this->messageTemplate('UNPAID-BILL');

        if (!$messageTemplate) {
            Log::warning('Message template for UNPAID-BILL not found.');
            return;
        }

        $this->sendMessage(
            $this->data['whatsapp_number'],
            $this->replacePlaceholders($messageTemplate->message, $placeholders),
        );
    }
}
