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
        Log::debug('SMS Sending Command Started');

        $smsNotification = Setting::where('key', 'sms_notifications')->first();

        if (!$smsNotification || $smsNotification->value != 1) {
            Log::warning('SMS notifications disabled.');
            $this->warn('SMS notifications disabled.');
            return 0;
        }

        // Load API settings
        $settings = Setting::whereIn('key', [
            'sms_api_url', 'sms_port', 'sms_username', 'sms_password'
        ])->pluck('value', 'key')->toArray();

        $apiUrl    = $settings['sms_api_url'] ?? null;
        $port      = $settings['sms_port'] ?? null;
        $username  = $settings['sms_username'] ?? null;
        $password  = $settings['sms_password'] ?? null;

        if (!$apiUrl || !$port || !$username || !$password) {
            Log::error('Missing SMS API configuration settings.');
            $this->error('Missing SMS API configuration.');
            return 1;
        }

        // Fetch all pending or failed messages
        Message::where('status', 'outgoing')
            ->whereIn('is_sent', [0, 2])
            ->chunk(50, function ($messages) use ($apiUrl, $port, $username, $password) {
                $this->processMessages($messages, $apiUrl, $port, $username, $password);
            });

        $this->info('SMS sending complete.');
        Log::debug('SMS Sending Command Completed');

        return 0;
    }

    // protected function processMessages($messages, $apiUrl, $port, $username, $password, $isRetry = false)
    // {
    //     foreach ($messages as $message) {

    //         try {
    //             // Encode message properly
    //             $encodedMessage = urlencode($message->message);

    //             // Build query
    //             $queryString = http_build_query([
    //                 'username'    => $username,
    //                 'password'    => $password,
    //                 'phonenumber' => $message->phone_number,
    //                 'message'     => $encodedMessage,
    //                 'port'        => $port,
    //                 'report'      => 'JSON',
    //                 'timeout'     => '0',
    //             ]);

    //             $url = "$apiUrl?$queryString";

    //             Log::info("Sending SMS → {$message->phone_number} | URL: $url");

    //             // CURL request
    //             $ch = curl_init();
    //             curl_setopt($ch, CURLOPT_URL, $url);
    //             curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    //             $response = curl_exec($ch);
    //             $curlError = curl_error($ch);

    //             curl_close($ch);

    //             // Handle CURL errors
    //             if ($response === false) {
    //                 throw new \Exception("CURL Failed: $curlError");
    //             }

    //             Log::debug("API Response for {$message->phone_number}: $response");

    //             // Try decoding JSON
    //             $json = json_decode($response, true);

    //             if (!is_array($json)) {
    //                 throw new \Exception("Invalid JSON Response: $response");
    //             }

    //             $report = $json['result'] ?? null;
    //             $phone  = $json['phonenumber'] ?? null;
    //             $time   = $json['time'] ?? null;

    //             // Check report status
    //             if ($report === "success" || $report === "sending") {

    //                 $message->update(['is_sent' => 1]);

    //                 Log::info("SMS Sent Successfully → {$message->phone_number} | Report: $report");
    //             } 
    //             else {

    //                 $message->update(['is_sent' => 2]);

    //                 Log::error("SMS Failed → {$message->phone_number} | API Report: $response");
    //             }

    //         } catch (\Exception $e) {

    //             $message->update(['is_sent' => 2]);

    //             Log::error("Error Sending SMS to {$message->phone_number}: {$e->getMessage()}");
    //         }

    //         // Add a retry delay (optional)
    //         if ($isRetry) {
    //             sleep(30);
    //         }
    //     }
    // }
    protected function processMessages($messages, $apiUrl, $port, $username, $password)
{
    foreach ($messages as $message) {

        try {
            // Encode message properly
            $encodedMessage = rawurlencode($message->message);

            // Build URL
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

            Log::info("Sending SMS → {$message->phone_number} | URL: $url");

            // CURL
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);

            $response = curl_exec($ch);
            $curlError = curl_error($ch);

            curl_close($ch);

            // ❌ 1. Handle CURL connection failure
            if ($response === false || !empty($curlError)) {

                $message->update(['is_sent' => 2]);

                Log::error("SMS FAILED (CURL ERROR) → {$message->phone_number}: $curlError");

                continue; // process next message
            }

            Log::debug("API Response → {$message->phone_number}: $response");

            // Try decoding JSON
            $json = json_decode($response, true);

            // ❌ 2. Invalid JSON = fail
            if (!is_array($json)) {

                $message->update(['is_sent' => 2]);

                Log::error("SMS FAILED (INVALID JSON) → {$message->phone_number}: $response");

                continue;
            }

            $report = $json['result'] ?? null;

            // ❌ 3. API says failure
            if ($report !== "success" && $report !== "sending") {

                $message->update(['is_sent' => 2]);

                Log::error("SMS FAILED (API ERROR) → {$message->phone_number}: $response");

                continue;
            }

            // ✅ SUCCESS
            $message->update(['is_sent' => 1]);

            Log::info("SMS SENT → {$message->phone_number} | Report: $report");

        } catch (\Exception $e) {

            $message->update(['is_sent' => 2]);

            Log::error("SMS FAILED (EXCEPTION) → {$message->phone_number}: {$e->getMessage()}");
        }
    }
}


}