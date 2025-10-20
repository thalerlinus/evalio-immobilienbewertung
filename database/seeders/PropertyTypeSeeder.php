<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PropertyTypeSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['key' => 'eigentumswohnung', 'label' => 'Eigentumswohnung', 'gnd' => 80, 'price_standard_eur' => 1499, 'request_only' => false],
            ['key' => 'einfamilienhaus', 'label' => 'Einfamilienhaus', 'gnd' => 80, 'price_standard_eur' => 1449, 'request_only' => false],
            ['key' => 'zweifamilienhaus', 'label' => 'Zweifamilienhaus', 'gnd' => 80, 'price_standard_eur' => 1549, 'request_only' => false],
            ['key' => 'dreifamilienhaus', 'label' => 'Dreifamilienhaus', 'gnd' => 80, 'price_standard_eur' => 1549, 'request_only' => false],
            ['key' => 'mfh_4_10', 'label' => 'Mehrfamilienhaus mit 4–10 WE', 'gnd' => 80, 'price_standard_eur' => 1699, 'request_only' => false],
            ['key' => 'mfh_10_plus', 'label' => 'Mehrfamilienhaus mit mehr als 10 WE', 'gnd' => 80, 'price_standard_eur' => null, 'request_only' => true],
            ['key' => 'wgh_10_minus', 'label' => 'Wohn- & Geschäftshaus mit bis zu 10 Einheiten', 'gnd' => 80, 'price_standard_eur' => 1699, 'request_only' => false],
            ['key' => 'wgh_10_plus', 'label' => 'Wohn- & Geschäftshaus mit mehr als 10 Einheiten', 'gnd' => 80, 'price_standard_eur' => null, 'request_only' => true],
            ['key' => 'gewerbeobjekt', 'label' => 'Gewerbeobjekt', 'gnd' => 60, 'price_standard_eur' => null, 'request_only' => true],
            ['key' => 'sonstiges', 'label' => 'Sonstiges', 'gnd' => null, 'price_standard_eur' => null, 'request_only' => true],
        ];

        foreach ($rows as &$row) {
            $row['created_at'] = now();
            $row['updated_at'] = now();
        }

        DB::table('property_types')->upsert($rows, ['key'], ['label', 'gnd', 'price_standard_eur', 'request_only', 'updated_at']);
    }
}
