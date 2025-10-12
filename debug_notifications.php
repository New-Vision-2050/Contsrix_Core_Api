<?php

/**
 * Debug script to check notification system setup
 * Run: php debug_notifications.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🔍 Checking Notification System Setup...\n\n";

// 1. Check Notification Settings
echo "1️⃣ Checking Notification Settings:\n";
echo str_repeat("-", 50) . "\n";

$settings = DB::table('notification_settings')->get();

if ($settings->isEmpty()) {
    echo "❌ No notification settings found in database!\n";
    echo "   Create one using:\n";
    echo "   INSERT INTO notification_settings (id, type, email, phone, reminder_type, is_active, created_at, updated_at)\n";
    echo "   VALUES (UUID(), 'mail', 'your-email@example.com', NULL, 'daily', 1, NOW(), NOW());\n\n";
} else {
    echo "✅ Found {$settings->count()} notification setting(s):\n";
    foreach ($settings as $setting) {
        $status = $setting->is_active ? '✅ Active' : '❌ Inactive';
        echo "   {$status} - Type: {$setting->type}, Email: {$setting->email}, Phone: {$setting->phone}, Reminder: {$setting->reminder_type}\n";
    }
    echo "\n";
}

// 2. Check Files with end_date
echo "2️⃣ Checking Files with end_date:\n";
echo str_repeat("-", 50) . "\n";

$today = date('Y-m-d');
$filesTotal = DB::table('files')->whereNotNull('end_date')->count();
$filesDue = DB::table('files')->whereNotNull('end_date')->where('end_date', '<=', $today)->count();

echo "   Total files with end_date: {$filesTotal}\n";
echo "   Files due/overdue (end_date <= today): {$filesDue}\n";

if ($filesDue > 0) {
    echo "   ✅ Sample files that need notification:\n";
    $sampleFiles = DB::table('files')
        ->whereNotNull('end_date')
        ->where('end_date', '<=', $today)
        ->limit(5)
        ->get(['id', 'name', 'reference_number', 'end_date']);
    
    foreach ($sampleFiles as $file) {
        echo "      - {$file->name} ({$file->reference_number}) - End Date: {$file->end_date}\n";
    }
} else {
    echo "   ❌ No files found that need notification!\n";
    echo "   Create test file:\n";
    echo "   INSERT INTO files (id, name, reference_number, start_date, end_date, created_at, updated_at)\n";
    echo "   VALUES (UUID(), 'Test File', 'TEST-001', '2024-01-01', '{$today}', NOW(), NOW());\n";
}
echo "\n";

// 3. Check Documents with notification_date
echo "3️⃣ Checking Documents with notification_date:\n";
echo str_repeat("-", 50) . "\n";

$docsTotal = DB::table('company_official_documents')->whereNotNull('notification_date')->count();
$docsDue = DB::table('company_official_documents')->whereNotNull('notification_date')->where('notification_date', '<=', $today)->count();

echo "   Total documents with notification_date: {$docsTotal}\n";
echo "   Documents due/overdue (notification_date <= today): {$docsDue}\n";

if ($docsDue > 0) {
    echo "   ✅ Sample documents that need notification:\n";
    $sampleDocs = DB::table('company_official_documents')
        ->whereNotNull('notification_date')
        ->where('notification_date', '<=', $today)
        ->limit(5)
        ->get(['id', 'company_id', 'notification_date']);
    
    foreach ($sampleDocs as $doc) {
        echo "      - Document ID: {$doc->id} - Notify Date: {$doc->notification_date}\n";
    }
} else {
    echo "   ❌ No documents found that need notification!\n";
}
echo "\n";

// 4. Check Mail Configuration
echo "4️⃣ Checking Mail Configuration:\n";
echo str_repeat("-", 50) . "\n";

$mailDriver = env('MAIL_MAILER');
$mailHost = env('MAIL_HOST');
$mailPort = env('MAIL_PORT');
$mailUsername = env('MAIL_USERNAME');
$mailFrom = env('MAIL_FROM_ADDRESS');

echo "   Mail Driver: " . ($mailDriver ?: '❌ NOT SET') . "\n";
echo "   Mail Host: " . ($mailHost ?: '❌ NOT SET') . "\n";
echo "   Mail Port: " . ($mailPort ?: '❌ NOT SET') . "\n";
echo "   Mail Username: " . ($mailUsername ? '✅ SET' : '❌ NOT SET') . "\n";
echo "   Mail From: " . ($mailFrom ?: '❌ NOT SET') . "\n";

if (!$mailDriver || !$mailHost) {
    echo "   ❌ Mail configuration incomplete!\n";
}
echo "\n";

// 5. Check Queue Configuration
echo "5️⃣ Checking Queue Configuration:\n";
echo str_repeat("-", 50) . "\n";

$queueDriver = env('QUEUE_CONNECTION', 'sync');
echo "   Queue Driver: {$queueDriver}\n";

if ($queueDriver === 'sync') {
    echo "   ⚠️ Using 'sync' driver - jobs run immediately (no queue worker needed)\n";
} else {
    echo "   ✅ Using '{$queueDriver}' driver - queue worker required\n";
    echo "   Run: php artisan queue:work\n";
}
echo "\n";

// 6. Check Jobs Table (if using database queue)
if ($queueDriver === 'database') {
    echo "6️⃣ Checking Jobs Queue:\n";
    echo str_repeat("-", 50) . "\n";
    
    $pendingJobs = DB::table('jobs')->count();
    $failedJobs = DB::table('failed_jobs')->count();
    
    echo "   Pending jobs: {$pendingJobs}\n";
    echo "   Failed jobs: {$failedJobs}\n";
    
    if ($failedJobs > 0) {
        echo "   ⚠️ Check failed jobs with: php artisan queue:failed\n";
    }
    echo "\n";
}

// Summary
echo str_repeat("=", 50) . "\n";
echo "📋 SUMMARY:\n";
echo str_repeat("=", 50) . "\n";

$issues = [];

if ($settings->isEmpty()) {
    $issues[] = "No notification settings configured";
}

if ($settings->where('is_active', 1)->isEmpty()) {
    $issues[] = "No active notification settings";
}

if ($filesDue === 0 && $docsDue === 0) {
    $issues[] = "No files or documents require notification";
}

if (!$mailDriver || !$mailHost) {
    $issues[] = "Mail configuration incomplete";
}

if (empty($issues)) {
    echo "✅ Everything looks good! Notifications should work.\n";
    echo "   Try running: php artisan notifications:send-document-notifications --force\n";
} else {
    echo "❌ Found " . count($issues) . " issue(s):\n";
    foreach ($issues as $i => $issue) {
        echo "   " . ($i + 1) . ". {$issue}\n";
    }
    echo "\n   Fix these issues and try again.\n";
}

echo "\n";
