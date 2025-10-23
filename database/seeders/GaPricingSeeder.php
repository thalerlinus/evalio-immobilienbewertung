<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GaPricingSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['key' => 'besichtigung', 'label' => 'Besichtigung', 'category' => 'package', 'sort_order' => 20, 'price_eur' => 350],
            ['key' => 'online', 'label' => 'Online', 'category' => 'package', 'sort_order' => 30, 'price_eur' => 50],
        ];

        $vatFactor = 1.19;

        foreach ($rows as &$row) {
            if ($row['price_eur'] !== null) {
                $row['price_eur'] = (int) round($row['price_eur'] / $vatFactor);
            }

            $row['created_at'] = now();
            $row['updated_at'] = now();
        }

        DB::table('ga_pricings')->upsert($rows, ['key'], ['label', 'category', 'sort_order', 'price_eur', 'updated_at']);
    }
}
