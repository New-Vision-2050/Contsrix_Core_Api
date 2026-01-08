<?php

declare(strict_types=1);

namespace Modules\NotificationSettings\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class FirebaseNotificationService
{
    public static function send(string $fcmToken, string $title, string $body, array $data = []): bool
    {
        if (!$fcmToken) {
            return false;
        }
            $dir = config('services.firebase.credentials');
            $firebase = (new Factory)
                ->withServiceAccount($dir);
            $messaging = $firebase->createMessaging();

            $message = CloudMessage::fromArray([
                'token' => $fcmToken,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                    'sound' => 'default',
                    'icon' => 'ic_notification',
                ],
                'android' => [
                    'notification' => [
                        'icon' => 'ic_notification',
                        'sound' => 'default',
                    ],
                ],
                'apns' => [
                    'payload' => [
                        'aps' => [
                            'sound' => 'default',
                        ],
                    ],
                ],
                'data' => $data,
            ]);

        try {
            $messaging->send($message);
            return true;
        } catch (\Throwable $e) {
            \Log::error('FCM Error: ' . $e->getMessage());
            return false;
        }
    }


    public static function sendSilent(string $fcmToken, array $data = []): bool
    {
        if (!$fcmToken) {
            return false;
        }

        $dir = config('services.firebase.credentials');
        $firebase = (new Factory)
            ->withServiceAccount($dir);
        $messaging = $firebase->createMessaging();

        $message = CloudMessage::fromArray([
            'token' => $fcmToken,
            'data' => $data,
            'android' => [
                'priority' => 'high',
            ],
            'apns' => [
                'payload' => [
                    'aps' => [
                        'content-available' => 1,
                        'sound' => '',
                    ],
                ],
            ],
        ]);

        try {
            $messaging->send($message);
            return true;
        } catch (\Throwable $e) {
            \Log::error('FCM Silent Error: ' . $e->getMessage());
            return false;
        }
    }
}
