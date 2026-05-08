<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestMailCommand extends Command
{
    protected $signature = 'kaan:mail:test {--to= : Destination email address}';
    protected $description = 'Sends a smoke test email to verify SMTP configuration';

    public function handle()
    {
        $to = $this->option('to');

        if (!$to) {
            $this->error('Please provide a destination email using --to=email@example.com');
            return 1;
        }

        try {
            Mail::raw('This is a smoke test from Kaan Core Backend to verify SMTP functionality.', function ($message) use ($to) {
                $message->to($to)->subject('Kaan Core SMTP Smoke Test');
            });
            $this->info("Smoke test email queued/sent successfully to {$to}.");
            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to send email: " . $e->getMessage());
            return 1;
        }
    }
}
