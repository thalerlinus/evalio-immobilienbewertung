<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GaPricingSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['key' => '1_we', 'label' => 'GA - 1 WE', 'price_eur' => 1099],
            ['key' => '2_3_we', 'label' => 'GA - 2–3 WE', 'price_eur' => 1199],
            ['key' => '4_11_we', 'label' => 'GA - 4–11 WE', 'price_eur' => 1349],
            ['key' => 'anfrage', 'label' => 'GA - Anfrage', 'price_eur' => null],
            ['key' => 'besichtigung', 'label' => 'Besichtigung', 'price_eur' => 350],
            ['key' => 'online', 'label' => 'Online', 'price_eur' => 50],
        ];

        foreach ($rows as &$row) {
            $row['created_at'] = now();
            $row['updated_at'] = now();
        }

        DB::table('ga_pricings')->upsert($rows, ['key'], ['label', 'price_eur', 'updated_at']);
    }
}
