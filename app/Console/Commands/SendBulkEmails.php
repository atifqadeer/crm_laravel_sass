<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Horsefly\SentEmail;
use Horsefly\SmtpSetting;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendBulkEmails extends Command
{
    protected $signature = 'emails:send-bulk';
    protected $description = 'Send bulk emails in chunks of 100 with 30-second delays for unsent emails (status=0)';

    public function handle(): void
    {
        Log::debug('SendBulkEmails command started.');
        $this->info('Starting email dispatch for unsent records...');

        $emails = SentEmail::where('status', '0')->get();
        if ($emails->isEmpty()) {
            Log::info('No unsent emails found with status=0.');
            $this->info('No unsent emails found.');
            return;
        }

        // Load logo as Base64
        $imagePath = public_path('images/logo-light22.png');
        $base64Image = file_exists($imagePath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($imagePath)) : '';

        SentEmail::where('status', '0')
            ->chunk(100, function ($emails) use ($base64Image) {
                foreach ($emails as $email) {
                    try {
                        $smtp = SmtpSetting::where('from_address', $email->sent_from)->first();

                        if (!$smtp) {
                            Log::warning("SMTP setting not found for sent_from: {$email->sent_from}, email ID: {$email->id}");
                            $this->warn("SMTP setting not found for email ID: {$email->id}");
                            continue;
                        }

                        config([
                            'mail.mailers.smtp.transport' => $smtp->mailer ?? 'smtp',
                            'mail.mailers.smtp.host' => $smtp->host,
                            'mail.mailers.smtp.port' => $smtp->port,
                            'mail.mailers.smtp.username' => $smtp->username,
                            'mail.mailers.smtp.password' => $smtp->password,
                            'mail.mailers.smtp.encryption' => $smtp->encryption ?? 'tls',
                            'mail.from.address' => $smtp->from_address,
                            'mail.from.name' => $smtp->from_name,
                        ]);

                        $ccEmails = !empty($email->cc_emails) ? array_filter(array_map('trim', explode(',', $email->cc_emails))) : [];

                        Mail::send('emails.bulk', [
                            'subject' => $email->subject ?? 'Bulk Email',
                            'template' => $email->template ?? 'This is a bulk email sent via cron job.',
                            'from_address' => $smtp->from_address,
                            'from_name' => $smtp->from_name,
                            'base64Image' => $base64Image,
                        ], function ($message) use ($email, $smtp, $ccEmails) {
                            $message->to($email->sent_to)
                                    ->subject($email->subject ?? 'Bulk Email')
                                    ->from($smtp->from_address, $smtp->from_name);
                            if (!empty($ccEmails)) {
                                $message->cc($ccEmails);
                            }
                        });

                        $email->update(['status' => '1']);

                        $this->info("Email sent successfully to {$email->sent_to} using SMTP ID: {$smtp->id}");
                        Log::info("Email sent to {$email->sent_to} using SMTP ID: {$smtp->id}, Email ID: {$email->id}");

                    } catch (\Exception $e) {
                        $this->error("Failed to send email ID: {$email->id}. Error: {$e->getMessage()}");
                        Log::error("Failed to send email ID: {$email->id}. Error: {$e->getMessage()}");
                    }
                }

                $this->info('Waiting 30 seconds before processing next chunk...');
                Log::debug('Waiting 30 seconds before processing next chunk.');
                sleep(30);
            });

        $this->info('All emails processed.');
        Log::debug('SendBulkEmails command completed.');
    }
}