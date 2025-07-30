<?php

namespace App\Jobs;

use App\Traits\SendMessageTrait;
use App\Traits\WhatsappConfigTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendWhatsappMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, WhatsappConfigTrait, SendMessageTrait;

    protected mixed $whatsappNumber;
    protected string $message;

    /**
     * @param mixed $whatsappNumber
     * @param string $message
     */
    public function __construct(mixed $whatsappNumber, string $message)
    {
        $this->whatsappNumber = $whatsappNumber;
        $this->message = $message;
    }

    public function handle(): void
    {
        $this->sendMessage($this->whatsappNumber, $this->message);
    }
}
