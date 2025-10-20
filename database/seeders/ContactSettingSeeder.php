<?php

namespace Database\Seeders;

use App\Models\ContactSetting;
use Illuminate\Database\Seeder;

class ContactSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            [
                'key' => 'support_name',
                'label' => 'Support Ansprechpartner',
                'type' => 'text',
                'value' => 'Ihr Evalio-Team',
            ],
            [
                'key' => 'support_email',
                'label' => 'Support E-Mail',
                'type' => 'email',
                'value' => 'kontakt@evalio.de',
            ],
            [
                'key' => 'support_phone',
                'label' => 'Support Telefon (intern)',
                'type' => 'phone',
                'value' => '+49 9999 99999',
            ],
            [
                'key' => 'support_phone_display',
                'label' => 'Support Telefon (Anzeige)',
                'type' => 'text',
                'value' => '+49 9999 99999',
            ],
            [
                'key' => 'admin_notification_email',
                'label' => 'Admin Benachrichtigung E-Mail',
                'type' => 'email',
                'value' => 'admin@evalio.de',
            ],
        ];

        foreach ($settings as $setting) {
            ContactSetting::query()->updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
