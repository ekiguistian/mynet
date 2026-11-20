<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use Illuminate\Database\Seeder;

class DefaultApplicationSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            'app_name' => 'mynet',
            'seo_title' => 'mynet — Billing ISP',
            'seo_description' => 'Pengelolaan layanan internet, pelanggan, tagihan, dan jaringan mynet.',
            'seo_keywords' => 'mynet, ISP, internet, billing',
            'google_search_console_url' => '',
            'support_phone' => '',
            'social_media' => '{}',
        ];

        foreach ($settings as $key => $value) {
            AppSetting::query()->updateOrCreate(
                ['setting_key' => $key],
                ['setting_value' => $value],
            );
        }
    }
}
