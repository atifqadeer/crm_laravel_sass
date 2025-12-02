<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Horsefly\Message;
use Illuminate\Support\Facades\Log;
use Horsefly\Setting;

class SendBulkSMS extends Command
{
    protected $signature = 'sms:send-bulk';
    protected $description = 'Send SMS via API in chunks of 50';

    public function handle()
    {
        $smsNotification = Setting::where('key', 'sms_notifications')->first();

        if($smsNotification){
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
            Message::where('status', 'outgoing')
                ->whereIn('is_sent', [0, 2])
                ->chunk(50, function ($messages) use ($apiUrl, $port, $username, $password) {
                    $this->processMessages($messages, $apiUrl, $port, $username, $password);
                });

            $this->info('SMS sending process completed.');

            return 0;
        }else{
            Log::debug('SMS sending command not completed, Because SMS Notifications are disabled. So, Please contact your admin.');
            Log::info('SMS sending command not completed, Because SMS Notifications are disabled. So, Please contact your admin.');
        }
    }

    protected function processMessages($messages, $apiUrl, $port, $username, $password, $isRetry = false)
    {
        foreach ($messages as $message) {
            try {
                // URL encode the message
                $encodedMessage = $message->message;

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

                $urls = "$apiUrl?$queryString";

                $url = str_replace(" ","%20",$urls);
                $link = curl_init();
                curl_setopt($link, CURLOPT_HEADER, 0);
                curl_setopt($link, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($link, CURLOPT_URL, $url);

                $response = curl_exec($link);

                if ($response == false) {
                    $error = curl_error($link);
                    curl_close($link);
                    throw new \Exception("Empty API response. CURL error: $error");
                }

                curl_close($link);

                $data = json_decode($response, true);

                if (!is_array($data)) {
                    throw new \Exception("Invalid JSON response: $response");
                }

                $report = $data['result'] ?? null;
                $time   = $data['time'] ?? null;
                $phone  = $data['phonenumber'] ?? null;

                if ($report === "success" || $report === "sending") {
                    $message->update(['is_sent' => 1]);

                    return [
                        'result'      => 'success',
                        'data'        => $response,
                        'phonenumber' => $phone,
                        'time'        => $time,
                        'report'      => $report,
                    ];
                }

                $message->update(['is_sent' => 2]);

                return [
                    'result' => 'error',
                    'data'   => $response,
                    'report' => $report,
                ];
            } catch (\Exception $e) {
                $message->update([
                    'is_sent' => 2 /** failed */
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