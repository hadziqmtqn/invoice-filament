<?php

namespace App\Jobs;

use App\Models\BankAccount;
use App\Models\Invoice;
use App\Traits\SendMessageTrait;
use App\Traits\WhatsappConfigTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class InvoiceWillDueMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, WhatsappConfigTrait, SendMessageTrait;

    protected Invoice $invoice;

    /**
     * @param Invoice $invoice
     */
    public function __construct(Invoice $invoice)
    {
        $this->invoice = $invoice;
    }

    public function handle(): void
    {
        $bankAccounts = BankAccount::query()
            ->with('bank:id,short_name')
            ->active()
            ->get();

        $placeholders = [
            '[Nama]' => $this->invoice->user?->name,
            '[Jenis Tagihan]' => $this->invoice->title,
            '[Jumlah]' => number_format($this->invoice->total_due,0,',','.'),
            '[Tanggal]' => Carbon::parse($this->invoice->due_date)->isoFormat('D MMMM Y'),
            '[Nomor Rekening]' => implode("\n", $bankAccounts->map(function (BankAccount $account) {
                return $account->bank?->short_name . " - " . $account->account_number . " (" . $account->account_name . ")";
            })->toArray()),
        ];

        $messageTemplate = $this->messageTemplate('TAGIHAN-AKAN-JATUH-TEMPO');

        if (!$messageTemplate) {
            Log::warning('Message template for TAGIHAN-AKAN-JATUH-TEMPO not found.');
            return;
        }

        $this->sendMessage(
            $this->invoice->user?->userProfile?->phone,
            $this->replacePlaceholders($messageTemplate->message, $placeholders)
        );
    }
}
