<?php

namespace Database\Seeders;

use App\Models\Region;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProvincesSeeder extends Seeder
{
    public function run(): void
    {
        $map = [
            'AND' => [ ['SE','Sevilla'], ['CO','Córdoba'], ['CA','Cádiz'], ['H','Huelva'],
                    ['MA','Málaga'], ['GR','Granada'], ['J','Jaén'], ['AL','Almería'] ],
            'ARA' => [ ['Z','Zaragoza'], ['HU','Huesca'], ['TE','Teruel'] ],
            'AST' => [ ['O','Asturias'] ],
            'BAL' => [
                ['PM','Illes Balears'], ['PM-MA','Mallorca'], ['PM-ME','Menorca'], ['PM-IBF','Eivissa-Formentera'],
            ],
            'CAN' => [ ['GC','Las Palmas'], ['TF','Santa Cruz de Tenerife'] ],
            'CANT'=> [ ['S','Cantabria'] ],                 // <-- NUEVO
            'CLM' => [ ['AB','Albacete'], ['CR','Ciudad Real'], ['CU','Cuenca'], ['GU','Guadalajara'], ['TO','Toledo'] ],
            'CYL' => [ ['AV','Ávila'], ['BU','Burgos'], ['LE','León'], ['P','Palencia'], ['SA','Salamanca'],
                    ['SG','Segovia'], ['SO','Soria'], ['VA','Valladolid'], ['ZA','Zamora'] ],
            'CAT' => [ ['B','Barcelona'], ['GI','Girona'], ['L','Lleida'], ['T','Tarragona'] ],
            'CV'  => [ ['A','Alicante'], ['CS','Castellón'], ['V','Valencia'] ],   // <-- ANTES 'VAL'
            'EXT' => [ ['BA','Badajoz'], ['CC','Cáceres'] ],
            'GAL' => [ ['C','A Coruña'], ['LU','Lugo'], ['OR','Ourense'], ['PO','Pontevedra'] ],
            'MAD' => [ ['M','Madrid'] ],
            'MUR' => [ ['MU','Murcia'] ],
            'NAV' => [ ['NA','Navarra'] ],
            'RIO' => [ ['LO','La Rioja'] ],
            'PVA' => [ ['VI','Álava'], ['BI','Bizkaia'], ['SS','Gipuzkoa'] ],
            'CEU' => [ ['CE','Ceuta'] ],
            'MEL' => [ ['ML','Melilla'] ],
        ];

        foreach ($map as $regionCode => $provinces) {
            $region = Region::where('code', $regionCode)->first();
            if (!$region) continue;

            foreach ($provinces as [$code,$name]) {
                DB::table('provinces')->updateOrInsert(
                    ['code' => $code],
                    ['region_id' => $region->id, 'name' => $name, 'code' => $code, 'updated_at' => now(), 'created_at' => now()]
                );
            }
        }
    }
}
