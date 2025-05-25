<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestMailSendCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mail:test {email : The email address to send the test mail to}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test email to verify mail configuration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        
        $this->info("Sending test email to: {$email}");
        
        try {
            Mail::to($email)->send(new \App\Mail\TestMail());
            $this->info('Test email sent successfully!');
            $this->info('Email configuration is working correctly.');
        } catch (\Exception $e) {
            $this->error('Failed to send test email!');
            $this->error($e->getMessage());
            
            // Provide some helpful debugging information
            $this->line('');
            $this->line('Mail configuration debugging tips:');
            $this->line('1. Check your .env file for correct mail settings');
            $this->line('2. Verify MAIL_MAILER, MAIL_HOST, MAIL_PORT, MAIL_USERNAME, and MAIL_PASSWORD');
            $this->line('3. Make sure your mail server is reachable');
        }
        
        return Command::SUCCESS;
    }
}
