<?php

namespace App\Jobs;

use App\Traits\SendMessageTrait;
use App\Traits\WhatsappConfigTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ChangeAuthenticationMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, WhatsappConfigTrait, SendMessageTrait;

    protected array $data;
    protected mixed $whatsappNumber;

    /**
     * @param array $data
     * @param mixed $whatsappNumber
     */
    public function __construct(array $data, mixed $whatsappNumber)
    {
        $this->data = $data;
        $this->whatsappNumber = $whatsappNumber;
    }

    public function handle(): void
    {
        $placeholders = [
            '[Nama]' => $this->data['user_name'],
            '[Nama Aplikasi]' => $this->application()?->name,
            '[Tanggal]' => now()->isoFormat('DD MMM Y'),
            '[Waktu]' => now()->isoFormat('HH:mm'),
            '[password_changed]' => $this->data['password_changed'] ? '*TELAH DIUBAH*' : '_TIDAK DIUBAH_',
            '[Email]' => $this->data['email']
        ];

        $messageTemplate = $this->messageTemplate('UBAH-KATA-SANDI');

        if (!$messageTemplate) {
            Log::warning('Message template for UBAH-KATA-SANDI not found.');
            return;
        }

        $this->sendMessage(
            $this->whatsappNumber,
            $this->replacePlaceholders($messageTemplate->message, $placeholders),
        );
    }
}
