<?php

namespace Database\Seeders;

use App\Models\ElectionRace;
use Illuminate\Database\Seeder;

class ElectionRaceSeeder extends Seeder
{
    public function run(): void
    {
        $races = [
            ['name' => 'North Council Member', 'dhaaira' => 'B9-1', 'type' => 'council', 'sort_order' => 1],
            ['name' => 'North-West Council Member', 'dhaaira' => 'B9-2', 'type' => 'council', 'sort_order' => 2],
            ['name' => 'Mid-North Council Member', 'dhaaira' => 'B9-3', 'type' => 'council', 'sort_order' => 3],
            ['name' => 'Mid-South Council Member', 'dhaaira' => 'B9-4', 'type' => 'council', 'sort_order' => 4],
            ['name' => 'South Council Member', 'dhaaira' => 'B9-5', 'type' => 'council', 'sort_order' => 5],
            ['name' => 'South-East Council Member', 'dhaaira' => 'B9-6', 'type' => 'council', 'sort_order' => 6],
            ['name' => 'City Mayor',              'dhaaira' => null,   'type' => 'mayor',   'sort_order' => 7],
            ['name' => 'North WDC Member',     'dhaaira' => 'B9-1', 'type' => 'wdc',     'sort_order' => 8],
            ['name' => 'North-West WDC Member',     'dhaaira' => 'B9-2', 'type' => 'wdc',     'sort_order' => 9],
            ['name' => 'Mid-NorthWDC Member',     'dhaaira' => 'B9-3', 'type' => 'wdc',     'sort_order' => 10],
            ['name' => 'Mid-South WDC Member',     'dhaaira' => 'B9-4', 'type' => 'wdc',     'sort_order' => 11],
            ['name' => 'South WDC Member',     'dhaaira' => 'B9-5', 'type' => 'wdc',     'sort_order' => 12],
            ['name' => 'South-East WDC Member',     'dhaaira' => 'B9-6', 'type' => 'wdc',     'sort_order' => 13],
            ['name' => 'WDC Raeesa',             'dhaaira' => null,   'type' => 'raeesa',  'sort_order' => 14],
        ];

        foreach ($races as $race) {
            ElectionRace::firstOrCreate(['name' => $race['name']], $race);
        }
    }
}
