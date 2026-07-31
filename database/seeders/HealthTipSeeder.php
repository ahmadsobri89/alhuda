<?php

namespace Database\Seeders;

use App\Models\HealthTip;
use Illuminate\Database\Seeder;

class HealthTipSeeder extends Seeder
{
    public function run(): void
    {
        $tips = [
            [
                'title' => 'Air Sejuk Menyebabkan Batuk?',
                'image_path' => 'tips/air-sejuk-menyebabkan-batuk.png',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'title' => 'Hari-Hari Doktor Dengar...',
                'image_path' => 'tips/mitos-drip-iv.png',
                'sort_order' => 2,
                'is_active' => true,
            ],
        ];

        foreach ($tips as $tip) {
            HealthTip::updateOrCreate(['title' => $tip['title']], $tip);
        }
    }
}
