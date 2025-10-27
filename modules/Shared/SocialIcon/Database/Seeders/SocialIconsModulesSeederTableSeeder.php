<?php

namespace Modules\Shared\SocialIcon\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Shared\SocialIcon\Models\SocialIcon;
use Ranium\SeedOnce\Traits\SeedOnce;
use Ramsey\Uuid\Uuid;

class SocialIconsModulesSeederTableSeeder extends Seeder
{
    use SeedOnce;
    
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $socialIcons = [
            [
                'id' => Uuid::uuid4()->toString(),
                'name' => 'Facebook',
                'web_icon' => 'fab fa-facebook-f',
                'mobile_icon' => 'facebook-icon.png',
            ],
            [
                'id' => Uuid::uuid4()->toString(),
                'name' => 'Twitter',
                'web_icon' => 'fab fa-twitter',
                'mobile_icon' => 'twitter-icon.png',
            ],
            [
                'id' => Uuid::uuid4()->toString(),
                'name' => 'Instagram',
                'web_icon' => 'fab fa-instagram',
                'mobile_icon' => 'instagram-icon.png',
            ],
            [
                'id' => Uuid::uuid4()->toString(),
                'name' => 'LinkedIn',
                'web_icon' => 'fab fa-linkedin-in',
                'mobile_icon' => 'linkedin-icon.png',
            ],
            [
                'id' => Uuid::uuid4()->toString(),
                'name' => 'YouTube',
                'web_icon' => 'fab fa-youtube',
                'mobile_icon' => 'youtube-icon.png',
            ],
            [
                'id' => Uuid::uuid4()->toString(),
                'name' => 'WhatsApp',
                'web_icon' => 'fab fa-whatsapp',
                'mobile_icon' => 'whatsapp-icon.png',
            ],
            [
                'id' => Uuid::uuid4()->toString(),
                'name' => 'Telegram',
                'web_icon' => 'fab fa-telegram-plane',
                'mobile_icon' => 'telegram-icon.png',
            ],
            [
                'id' => Uuid::uuid4()->toString(),
                'name' => 'TikTok',
                'web_icon' => 'fab fa-tiktok',
                'mobile_icon' => 'tiktok-icon.png',
            ],
            [
                'id' => Uuid::uuid4()->toString(),
                'name' => 'Snapchat',
                'web_icon' => 'fab fa-snapchat-ghost',
                'mobile_icon' => 'snapchat-icon.png',
            ],
            [
                'id' => Uuid::uuid4()->toString(),
                'name' => 'Pinterest',
                'web_icon' => 'fab fa-pinterest-p',
                'mobile_icon' => 'pinterest-icon.png',
            ],
            [
                'id' => Uuid::uuid4()->toString(),
                'name' => 'Discord',
                'web_icon' => 'fab fa-discord',
                'mobile_icon' => 'discord-icon.png',
            ],
            [
                'id' => Uuid::uuid4()->toString(),
                'name' => 'Reddit',
                'web_icon' => 'fab fa-reddit-alien',
                'mobile_icon' => 'reddit-icon.png',
            ],
            [
                'id' => Uuid::uuid4()->toString(),
                'name' => 'Twitch',
                'web_icon' => 'fab fa-twitch',
                'mobile_icon' => 'twitch-icon.png',
            ],
            [
                'id' => Uuid::uuid4()->toString(),
                'name' => 'Skype',
                'web_icon' => 'fab fa-skype',
                'mobile_icon' => 'skype-icon.png',
            ],
            [
                'id' => Uuid::uuid4()->toString(),
                'name' => 'Viber',
                'web_icon' => 'fab fa-viber',
                'mobile_icon' => 'viber-icon.png',
            ],
        ];

        foreach ($socialIcons as $socialIcon) {
            SocialIcon::updateOrCreate(
                ['name' => $socialIcon['name']],
                $socialIcon
            );
        }
    }
}
