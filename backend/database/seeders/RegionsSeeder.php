<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RegionsSeeder extends Seeder
{
    public function run(): void
    {
        $regions = [
            ['name' => 'Andalucía',            'code' => 'AND'],
            ['name' => 'Aragón',               'code' => 'ARA'],
            ['name' => 'Principado de Asturias','code' => 'AST'],
            ['name' => 'Illes Balears',        'code' => 'BAL'],
            ['name' => 'Canarias',             'code' => 'CAN'],
            ['name' => 'Cantabria',            'code' => 'CANT'],
            ['name' => 'Castilla-La Mancha',   'code' => 'CLM'],
            ['name' => 'Castilla y León',      'code' => 'CYL'],
            ['name' => 'Cataluña',             'code' => 'CAT'],
            ['name' => 'Comunitat Valenciana', 'code' => 'CV'],
            ['name' => 'Extremadura',          'code' => 'EXT'],
            ['name' => 'Galicia',              'code' => 'GAL'],
            ['name' => 'Comunidad de Madrid',  'code' => 'MAD'],
            ['name' => 'Región de Murcia',     'code' => 'MUR'],
            ['name' => 'Navarra',              'code' => 'NAV'],
            ['name' => 'La Rioja',             'code' => 'RIO'],
            ['name' => 'País Vasco',           'code' => 'PVA'],
            ['name' => 'Ceuta',                'code' => 'CEU'],
            ['name' => 'Melilla',              'code' => 'MEL'],
        ];

        foreach ($regions as $r) {
            DB::table('regions')->updateOrInsert(['code' => $r['code']], $r);
        }
    }
}
