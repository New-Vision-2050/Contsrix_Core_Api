<?php

declare(strict_types=1);

namespace Modules\User\Presenters;

class InfoAlertPresenter
{
    private const ALERT_TITLES = [
        'work_permit' => 'لقد قاربت تصريح العمل على الانتهاء',
        'passport' => 'لقد قاربت جواز السفر على الانتهاء',
        'identity' => 'لقد قاربت الهوية على الانتهاء',
        'border_number' => 'لقد قاربت رقم الحدود على الانتهاء',
        'entry_number' => 'لقد قاربت رقم الدخول على الانتهاء',
        'qualification' => 'لقد قاربت المؤهل على الانتهاء',
        'bank_account' => 'يرجى ادخال حساب بنكي',
    ];

    public static function collection(array $alerts): array
    {
        return array_map(function ($alert) {
            return self::present($alert);
        }, $alerts);
    }

    public static function present(array $alert): array
    {
        return [
            'type' => $alert['type'],
            'title' => self::ALERT_TITLES[$alert['type']] ?? 'لقد قاربت على الانتهاء',
            'end_date' => $alert['end_date'],
            'user_id' => $alert['user_id'],
            'name' => $alert['name'],
            'days_remaining' => $alert['days_remaining'] ?? null,
        ];
    }
}
