<?php

namespace App\Traits;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\Log;

trait SendMessageTrait
{
    protected function sendMessage($whatsappNumber, $message): void
    {
        try {
            if ($this->whatsappConfig()?->provider === 'fonnte') {
                $request = new Request('POST', $this->whatsappConfig()?->api_domain, [
                    'Authorization' => $this->whatsappConfig()?->api_key
                ]);

                $res = (new Client())->sendAsync($request, [
                    'multipart' => [
                        [
                            'name' => 'target',
                            'contents' => $whatsappNumber
                        ],
                        [
                            'name' => 'message',
                            'contents' => $message
                        ]
                    ]
                ])
                    ->wait();

                // Log the response for debugging purposes
                Log::info('Whatsapp message sent successfully. Response: ' . $res->getBody());
            }
        } catch (GuzzleException $guzzleException) {
            Log::error($guzzleException->getMessage());
            return;
        }
    }
}
