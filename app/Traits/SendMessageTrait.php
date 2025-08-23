<?php

namespace App\Traits;

use App\Models\Application;
use App\Models\MessageTemplate;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\Log;

trait SendMessageTrait
{
    protected function application(): ?Application
    {
        return Application::first();
    }

    protected function messageTemplate($category): ?MessageTemplate
    {
        return MessageTemplate::whereHas('messageTemplateCategory', fn($query) => $query->where('code', $category))
            ->active()
            ->first();
    }

    protected function replacePlaceholders(string $messageTemplate, array $placeholders): array|string
    {
        return str_replace(array_keys($placeholders), array_values($placeholders), $messageTemplate);
    }
    
    protected function sendMessage($whatsappNumber, $message): void
    {
        try {
            $whatsappConfig = $this->whatsappConfig();

            if (!$whatsappConfig) {
                return;
            }

            // TODO WABLAS
            if ($whatsappConfig->provider === 'WABLAS') {
                $curl = curl_init();
                curl_setopt($curl, CURLOPT_HTTPHEADER, array("Authorization: " . $whatsappConfig->api_key . "." . $whatsappConfig->secret_key, "Content-Type: application/json"));
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode([
                    "data" => [
                        [
                            'phone' => $whatsappNumber,
                            'message' => $message,
                        ],
                    ]
                ]));
                curl_setopt($curl, CURLOPT_URL, $whatsappConfig->api_domain);
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
                curl_exec($curl);
                curl_close($curl);

                Log::info('WABLAS Response: ' . curl_exec($curl));
            }

            // TODO WANESIA
            if ($whatsappConfig->provider == 'WANESIA') {
                $response = (new Client())->sendAsync(new Request('POST', $whatsappConfig->api_domain), [
                    'form_params' => [
                        'token' => $whatsappConfig->api_key,
                        'number' => $whatsappNumber,
                        'message' => $message,
                    ]
                ])->wait();

                Log::info('WANESIA Response: ' . $response->getBody());
            }

            // TODO FONNTE
            if ($whatsappConfig->provider === 'fonnte') {
                $request = new Request('POST', $whatsappConfig->api_domain, [
                    'Authorization' => $whatsappConfig->api_key
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
                Log::info('Whatsapp message sent successfully', [
                    'status_code' => $res->getStatusCode(),
                    'body' => $res->getBody()->getContents()
                ]);
            }
        } catch (GuzzleException $guzzleException) {
            Log::error($guzzleException->getMessage());
            return;
        }
    }
}
