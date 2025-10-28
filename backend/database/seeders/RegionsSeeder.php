<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RegionsSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('regions')->insert([
            ['name' => 'Andalucía', 'code' => 'AN'],
            ['name' => 'Aragón', 'code' => 'AR'],
            ['name' => 'Asturias', 'code' => 'AS'],
            ['name' => 'Islas Baleares', 'code' => 'IB'],
            ['name' => 'Canarias', 'code' => 'CN'],
            ['name' => 'Cantabria', 'code' => 'CB'],
            ['name' => 'Castilla y León', 'code' => 'CL'],
            ['name' => 'Castilla-La Mancha', 'code' => 'CM'],
            ['name' => 'Cataluña', 'code' => 'CT'],
            ['name' => 'Comunidad Valenciana', 'code' => 'CV'],
            ['name' => 'Extremadura', 'code' => 'EX'],
            ['name' => 'Galicia', 'code' => 'GA'],
            ['name' => 'Madrid', 'code' => 'MD'],
            ['name' => 'Murcia', 'code' => 'MU'],
            ['name' => 'Navarra', 'code' => 'NA'],
            ['name' => 'País Vasco', 'code' => 'PV'],
            ['name' => 'La Rioja', 'code' => 'LR'],
            ['name' => 'Ceuta', 'code' => 'CE'],
            ['name' => 'Melilla', 'code' => 'ML'],
        ]);
    }
}
