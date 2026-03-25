<?php

namespace Database\Seeders;

use App\Models\Candidate;
use Illuminate\Database\Seeder;

class CandidateSeeder extends Seeder
{
    public function run(): void
    {
        // $races = [
        //   1  ['name' => 'North Council Member', 'dhaaira' => 'B9-1', 'type' => 'council', 'sort_order' => 1],
        //   2  ['name' => 'North-West Council Member', 'dhaaira' => 'B9-2', 'type' => 'council', 'sort_order' => 2],
        //   3  ['name' => 'Mid-North Council Member', 'dhaaira' => 'B9-3', 'type' => 'council', 'sort_order' => 3],
        //   4  ['name' => 'Mid-South Council Member', 'dhaaira' => 'B9-4', 'type' => 'council', 'sort_order' => 4],
        //   5  ['name' => 'South Council Member', 'dhaaira' => 'B9-5', 'type' => 'council', 'sort_order' => 5],
        //    6 ['name' => 'South-East Council Member', 'dhaaira' => 'B9-6', 'type' => 'council', 'sort_order' => 6],
        //   7  ['name' => 'City Mayor',              'dhaaira' => null,   'type' => 'mayor',   'sort_order' => 7],
        //  8   ['name' => 'North WDC Member',     'dhaaira' => 'B9-1', 'type' => 'wdc',     'sort_order' => 8],
        //   9  ['name' => 'North-West WDC Member',     'dhaaira' => 'B9-2', 'type' => 'wdc',     'sort_order' => 9],
        //   10  ['name' => 'Mid-NorthWDC Member',     'dhaaira' => 'B9-3', 'type' => 'wdc',     'sort_order' => 10],
        //   11  ['name' => 'Mid-South WDC Member',     'dhaaira' => 'B9-4', 'type' => 'wdc',     'sort_order' => 11],
        //   12  ['name' => 'South WDC Member',     'dhaaira' => 'B9-5', 'type' => 'wdc',     'sort_order' => 12],
        //   13  ['name' => 'South-East WDC Member',     'dhaaira' => 'B9-6', 'type' => 'wdc',     'sort_order' => 13],
        //   14  ['name' => 'WDC Raeesa',             'dhaaira' => null,   'type' => 'raeesa',  'sort_order' => 14],
        // ];

        $candidates = [
            ['name' => 'Ibrahim Hassan', 'address' => 'Roashan Haaru', 'affiliation' => 'PNC', 'race_id' => 7, 'candidate_number' => 1],
            ['name' => 'Mohamed Athif', 'address' => 'Hilman', 'affiliation' => 'MDP', 'race_id' => 7, 'candidate_number' => 2],

            ['name' => 'Ahmed Abdulla (Roazey)', 'affiliation' => 'MDP', 'address' => 'Iruvai', 'race_id' => 1, 'candidate_number' => 3],
            ['name' => 'Siraaj Mohamed', 'affiliation' => 'PNC', 'address' => 'Andhaaz', 'race_id' => 1, 'candidate_number' => 4],
            ['name' => 'Ibrahim Ahmed', 'affiliation' => 'PNC', 'address' => 'Vavathi', 'race_id' => 2, 'candidate_number' => 3],
            ['name' => 'Nasrullah Ismail', 'affiliation' => 'MDP', 'address' => 'Iramaage', 'race_id' => 2, 'candidate_number' => 4],
            ['name' => 'Maaidha Hassan Rasheed', 'affiliation' => 'PNC', 'address' => 'Nika', 'race_id' => 3, 'candidate_number' => 3],
            ['name' => 'Akbaree Mohamed', 'affiliation' => 'MDP', 'address' => 'Rio', 'race_id' => 3, 'candidate_number' => 4],
            ['name' => 'Mohamed Shaiman Adam Saleem', 'affiliation' => 'MDP', 'address' => 'Capricon', 'race_id' => 4, 'candidate_number' => 3],
            ['name' => 'Jaufaru Ali (Jaupics)', 'affiliation' => 'PNC', 'address' => 'Najaf', 'race_id' => 4, 'candidate_number' => 4],
            ['name' => 'Ahmed Abdul Raheem', 'affiliation' => 'PNC', 'address' => 'Felivaru', 'race_id' => 5, 'candidate_number' => 3],
            ['name' => 'Rilwan Mohamed', 'affiliation' => 'MDP', 'address' => 'Kashikeyogasdhashuge', 'race_id' => 5, 'candidate_number' => 4],
            ['name' => 'Fathimath Mohamed', 'affiliation' => 'PNC', 'address' => 'Alpha', 'race_id' => 6, 'candidate_number' => 3],
            ['name' => 'Salma Mohamed', 'affiliation' => 'MDP', 'address' => 'Penzeemaage', 'race_id' => 6, 'candidate_number' => 4],

            ['name' => 'Aminath Ibrahim', 'affiliation' => 'MDP', 'address' => 'Misraab', 'race_id' => 14, 'candidate_number' => 1],
            ['name' => 'Khadheeja Moosa', 'affiliation' => 'PNC', 'address' => 'Lunboamaage', 'race_id' => 14, 'candidate_number' => 2],

            ['name' => 'Fathimath Muna Adam', 'affiliation' => 'MDP', 'address' => 'Rasmu', 'race_id' => 8, 'candidate_number' => 3],
            ['name' => 'Saajidha Ibrahim', 'affiliation' => 'PNC', 'address' => 'Vindhu', 'race_id' => 8, 'candidate_number' => 4],

            ['name' => 'Maajidha Ali', 'affiliation' => 'MDP', 'address' => 'Dhunthari', 'race_id' => 9, 'candidate_number' => 3],
            ['name' => 'Ihsaana Adam', 'affiliation' => 'PNC', 'address' => 'Calvinia', 'race_id' => 9, 'candidate_number' => 4],

            ['name' => 'Fathimath Abdul Hannan (Hannah)', 'affiliation' => 'MDP', 'address' => 'Niyaama', 'race_id' => 10, 'candidate_number' => 3],
            ['name' => 'Fathimath Ahmed', 'affiliation' => 'PNC', 'address' => 'Araairu', 'race_id' => 10, 'candidate_number' => 4],

            ['name' => 'Naadhira Ibrahim', 'affiliation' => 'PNC', 'address' => 'Vaadheege', 'race_id' => 11, 'candidate_number' => 3],
            ['name' => 'Khadheeja Ali', 'affiliation' => 'MDP', 'address' => 'Mashaairu', 'race_id' => 11, 'candidate_number' => 4],

            ['name' => 'Yumna Ali', 'affiliation' => 'MDP', 'address' => 'Malaka', 'race_id' => 12, 'candidate_number' => 3],
            ['name' => 'Aminath Hassan', 'affiliation' => 'PNC', 'address' => 'Shady Corner', 'race_id' => 12, 'candidate_number' => 4],

            ['name' => 'Firaasha Ibrahim', 'affiliation' => 'MDP', 'address' => 'Bandaara Hiya C03', 'race_id' => 13, 'candidate_number' => 3],
            ['name' => 'Ameena Mohamed', 'affiliation' => 'PNC', 'address' => 'Ruvaamaage', 'race_id' => 13, 'candidate_number' => 4],
        ];

        foreach ($candidates as $candidate) {
            Candidate::firstOrCreate(['name' => $candidate['name']], $candidate);
        }
    }
}
