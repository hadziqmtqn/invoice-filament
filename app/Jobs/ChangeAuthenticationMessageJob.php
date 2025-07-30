<?php

namespace App\Jobs;

use App\Traits\SendMessageTrait;
use App\Traits\WhatsappConfigTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

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
            '[user_name]' => $this->data['user_name'],
            '[name_app]' => $this->application()?->name,
            '[date]' => now()->isoFormat('DD MMM Y'),
            '[time]' => now()->isoFormat('HH:mm'),
            '[password]' => $this->data['password'] ?? '',
            '[email]' => $this->data['email']
        ];

        $messageTemplate = $this->messageTemplate('change_authentication');

        if ($messageTemplate) {
            $this->sendMessage(
                $this->whatsappNumber,
                $this->replacePlaceholders($messageTemplate->message, $placeholders),
            );
        }
    }
}
