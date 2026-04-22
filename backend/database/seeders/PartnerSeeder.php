<?php

namespace Database\Seeders;

use App\Models\AppConfig;
use App\Models\Partner;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PartnerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Ensure AUTO_ADMIT config exists
        AppConfig::updateOrCreate(
            ['key' => 'AUTO_ADMIT'],
            [
                'value' => '1',
                'type' => 'boolean',
                'is_cached' => true,
            ]
        );

        // 2. Seed Startocode Partner
        Partner::updateOrCreate(
            ['slug' => 'startocode'],
            [
                'name' => 'Startocode',
                'active' => true,
                'api_credentials' => [
                    'base_url' => 'https://startocode.com/api/v2/partners/gh/integration',
                    'default_password' => 'Welcome@Startocode123!',
                    'materials_url' => 'https://startocode.com/dashboard',
                ]
            ]
        );
    }
}
