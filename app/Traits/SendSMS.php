<?php

namespace App\Traits;

use Horsefly\Message;
use Illuminate\Support\Facades\Log;
use Horsefly\SmsTemplate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

trait SendSMS
{
    public function saveSMSDB($sms_to, $message, $moduleType = null, $moduleId = null)
    {
        try {
            $sent_sms = new Message();
            $sent_sms->module_id  = $moduleId;
            $sent_sms->module_type  = $moduleType;
            $sent_sms->user_id       = Auth::id();
            $sent_sms->message       = $message;
            $sent_sms->phone_number  = $sms_to;
            $sent_sms->status  = 'outgoing';
            $sent_sms->msg_id = 'D' . mt_rand(1000000000000, 9999999999999);
            $sent_sms->date = Carbon::now()->toDateString(); // e.g., "2025-06-26"
            $sent_sms->time = Carbon::now()->toTimeString(); // e.g., "16:45:00"
            $sent_sms->save();

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to save sent sms: ' . $e->getMessage(), [
                'to'      => $sms_to,
                'Message' => $message,
                'user_id' => Auth::id(),
            ]);
            return false;
        }
    }
    // private function applicantSms($applicant_number, $applicant_name)
    // {
    //     // Clean and sanitize the name
    //     $applicant_name = trim(preg_replace('/[^a-zA-Z\s]/', '', $applicant_name));

    //     // Fetch SMS template from the database
    //     $template = SmsTemplate::where('title', 'applicant_welcome_sms')->where('status', 1)->first();

    //     if (!$template) {
    //         Log::warning('SMS template "applicant_welcome_sms" not found or inactive.');
    //         return false;
    //     }

    //     // Replace placeholders in template
    //     $message = str_replace('(applicant_name)', $applicant_name, $template->template);

    //     // URL encode the message
    //     $encoded_message = urlencode($message);

    //     // Build API query string
    //     $query_string = "http://milkyway.tranzcript.com:1008/sendsms?" . http_build_query([
    //         'username'     => 'admin',
    //         'password'     => 'admin',
    //         'phonenumber'  => $applicant_number,
    //         'message'      => $message,
    //         'port'         => '1',
    //         'report'       => 'JSON',
    //         'timeout'      => '0',
    //     ]);

    //     // Send SMS via cURL
    //     try {
    //         $ch = curl_init();
    //         curl_setopt($ch, CURLOPT_URL, $query_string);
    //         curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    //         $response = curl_exec($ch);
    //         curl_close($ch);

    //         $decoded = json_decode($response, true);

    //         if (!$decoded || !isset($decoded['result'])) {
    //             Log::error('Invalid SMS response: ' . $response);
    //             return false;
    //         }

    //         $result = strtolower($decoded['result']);

    //         return in_array($result, ['success', 'sending']);
    //     } catch (\Exception $e) {
    //         Log::error('Error sending SMS: ' . $e->getMessage());
    //         return false;
    //     }
    // }

}
