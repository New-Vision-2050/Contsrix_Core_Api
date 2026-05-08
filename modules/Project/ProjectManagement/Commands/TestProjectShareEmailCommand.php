<?php

namespace Modules\Project\ProjectManagement\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Modules\Project\ProjectManagement\Mail\ProjectShareMail;
use Modules\Project\ProjectManagement\Models\ProjectManagement;
use Modules\Shared\ResourceShare\Models\ResourceShare;

class TestProjectShareEmailCommand extends Command
{
    protected $signature = 'test:project-share-email {email}';
    protected $description = 'Test project share email sending';

    public function handle()
    {
        $email = $this->argument('email');
        
        $this->info("Testing project share email to: {$email}");
        
        try {
            // Create dummy data for testing
            $share = new ResourceShare([
                'id' => 'test-share-id',
                'notes' => 'هذا مشروع اختباري للتأكد من عمل البريد الإلكتروني',
                'status' => 'pending',
            ]);
            $share->created_at = now();
            
            $project = new ProjectManagement([
                'id' => 'test-project-id',
                'name' => 'مشروع تجريبي',
                'description' => 'وصف المشروع التجريبي',
            ]);
            
            $recipientName = 'أحمد محمد';
            $senderName = 'محمد علي';
            $actionUrl = 'https://constrix.com/ar/projects/inbox';
            
            // Try to send the email
            $this->info("Sending email...");
            
            Mail::to($email)->send(
                new ProjectShareMail(
                    share: $share,
                    project: $project,
                    recipientName: $recipientName,
                    senderName: $senderName,
                    actionUrl: $actionUrl
                )
            );
            
            $this->info("✅ Email sent successfully!");
            $this->info("Check your inbox at: {$email}");
            
            // Check mail configuration
            $this->info("\n📧 Mail Configuration:");
            $this->line("Driver: " . config('mail.default'));
            $this->line("From: " . config('mail.from.address'));
            
        } catch (\Exception $e) {
            $this->error("❌ Error sending email:");
            $this->error($e->getMessage());
            $this->error("\nStack trace:");
            $this->error($e->getTraceAsString());
            
            return 1;
        }
        
        return 0;
    }
}
