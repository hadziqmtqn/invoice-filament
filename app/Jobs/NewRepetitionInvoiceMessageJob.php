<?php

namespace App\Jobs;

use App\Models\RecurringInvoice;
use App\Traits\SendMessageTrait;
use App\Traits\WhatsappConfigTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class NewRepetitionInvoiceMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, WhatsappConfigTrait, SendMessageTrait;

    protected RecurringInvoice $recurringInvoice;

    /**
     * @param RecurringInvoice $recurringInvoice
     */
    public function __construct(RecurringInvoice $recurringInvoice)
    {
        $this->recurringInvoice = $recurringInvoice;
    }

    public function handle(): void
    {
        $placeholders = [
            '[Nama Pelanggan]' => $this->recurringInvoice->user?->name,
            '[Jenis Tagihan]' => $this->recurringInvoice->title,
            '[Jumlah]' => number_format($this->recurringInvoice->total_price,0,',','.'),
            '[Tanggal]' => Carbon::parse($this->recurringInvoice->start_generate_date)->isoFormat('D MMMM Y'),
        ];

        $messageTemplate = $this->messageTemplate('PENGINGAT-FAKTUR-PERULANGAN-BARU');

        if (!$messageTemplate) {
            Log::warning('Message template for PENGINGAT-FAKTUR-PERULANGAN-BARU not found.');
            return;
        }

        // Kirim ke no whatsapp admin
        $this->sendMessage(
            $this->whatsappConfig()?->admin_number,
            $this->replacePlaceholders($messageTemplate->message, $placeholders)
        );
    }
}
