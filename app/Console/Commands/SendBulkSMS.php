<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Horsefly\ApplicantMessage;
use Illuminate\Support\Facades\Log;
use Horsefly\Setting;

class SendBulkSMS extends Command
{
    protected $signature = 'sms:send-bulk';
    protected $description = 'Send SMS via API in chunks of 50';

    public function handle()
    {
        // Fetch settings once
        $settings = Setting::whereIn('key', ['sms_api_url', 'sms_port', 'sms_username', 'sms_password'])
            ->pluck('value', 'key')
            ->toArray();

        $apiUrl = $settings['sms_api_url'] ?? null;
        $port = $settings['sms_port'] ?? null;
        $username = $settings['sms_username'] ?? null;
        $password = $settings['sms_password'] ?? null;

        // Validate settings
        if (!$apiUrl || !$port || !$username || !$password) {
            Log::error('Missing SMS API configuration settings.');
            $this->error('SMS sending failed: Missing configuration settings.');
            return 1;
        }

        // Process new and failed messages (is_sent = 0 or 2)
        ApplicantMessage::where('status', 'outgoing')
            ->whereIn('is_sent', [0, 2])
            ->chunk(50, function ($messages) use ($apiUrl, $port, $username, $password) {
                $this->processMessages($messages, $apiUrl, $port, $username, $password);
            });

        // Process sending messages (is_sent = 3)
        // ApplicantMessage::where('status', 'outgoing')
        //     ->where('is_sent', 3)
        //     ->chunk(50, function ($messages) use ($apiUrl, $port, $username, $password) {
        //         $this->processMessages($messages, $apiUrl, $port, $username, $password, true);
        //     });

        $this->info('SMS sending process completed.');
        return 0;
    }

    protected function processMessages($messages, $apiUrl, $port, $username, $password, $isRetry = false)
    {
        foreach ($messages as $message) {
            try {
                // URL encode the message
                $encodedMessage = urlencode($message->message);

                // Build API query string to match provided format
                $queryString = http_build_query([
                    'username'    => $username,
                    'password'    => $password,
                    'phonenumber' => $message->phone_number,
                    'message'     => $encodedMessage,
                    'port'        => $port,
                    'report'      => 'JSON',
                    'timeout'     => '0',
                ]);

                $url = "$apiUrl?$queryString";

                // Send SMS via cURL
                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL            => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT        => 30,
                    CURLOPT_FAILONERROR    => true,
                    CURLOPT_FOLLOWLOCATION => true,
                ]);

                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                $decoded = json_decode($response, true);

                if ($httpCode >= 200 && $httpCode < 300 && $decoded && isset($decoded['result'])) {
                    $result = strtolower($decoded['result']);
                    $time = $decoded['time'] ?? null;
                    $phone = $decoded['phonenumber'] ?? $message->phone_number;

                    if ($result === 'success') {
                        $message->update([
                            'is_sent' => 1,
                            'status' => 'sent',
                            'sent_at' => now(),
                            'retry_count' => 0,
                        ]);
                        Log::info("SMS sent successfully to {$phone} (ID: {$message->id}, Time: {$time})");
                    } elseif ($result === 'sending') {
                        $message->update([
                            'is_sent' => 3
                        ]);
                        Log::info("SMS to {$phone} (ID: {$message->id}, Time: {$time}) is sending");
                    } else {
                        $message->update([
                            'is_sent' => 2,
                            'status' => 'failed',
                            'sent_at' => now(),
                            'retry_count' => 0,
                        ]);
                        Log::error("SMS to {$phone} (ID: {$message->id}) failed with result: {$result}");
                    }
                } else {
                    $message->update([
                        'is_sent' => 2,
                        'status' => 'failed',
                        'sent_at' => now(),
                        'retry_count' => 0,
                    ]);
                    Log::error("SMS to {$message->phone_number} (ID: {$message->id}) failed: " . ($response ?: 'No response'));
                }
            } catch (\Exception $e) {
                $message->update([
                    'is_sent' => 2,
                    'status' => 'failed',
                    'sent_at' => now(),
                    'retry_count' => 0,
                ]);
                Log::error("Error sending SMS to {$message->phone_number} (ID: {$message->id}): {$e->getMessage()}");
            }

            // Add delay for retries to avoid overwhelming the API
            if ($isRetry) {
                sleep(30); // Wait 30 seconds between retries
            }
        }
    }
}