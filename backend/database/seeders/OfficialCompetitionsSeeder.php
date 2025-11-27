<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OfficialCompetitionsSeeder extends Seeder
{
    public function run(): void
    {
        // ===== Helpers
        $now = now();

        $regId = fn(?string $code) => $code
            ? DB::table('regions')->where('code', $code)->value('id')
            : null;

        $seasonId = fn(string $code) =>
            DB::table('seasons')->where('code', $code)->value('id');

        $catId = fn(string $name, string $level, string $gender) =>
            DB::table('categories')->where(compact('name','level','gender'))->value('id');

        $createLeague = function (array $attrs) use ($now) {
            // Clave "única" lógica para evitar duplicados: name + season_id
            $where = [
                'name'      => $attrs['name'],
                'season_id' => $attrs['season_id'],
            ];
            $defaults = array_merge([
                'type'          => 'official',
                'visibility'    => 'public',
                'access_uuid'   => null,
                'owner_user_id' => null,
                'is_active'     => true,
                'created_at'    => $now,
                'updated_at'    => $now,
            ], $attrs);

            DB::table('leagues')->updateOrInsert($where, $defaults);
        };

        // ===== Season codes a sembrar
        $seasons = ['2024/25','2025/26'];

        // ===== Categorías base
        $sen_m_pro  = $catId('Senior','pro','male');
        $sen_m_semi = $catId('Senior','semi','male');
        $sen_m_am   = $catId('Senior','amateur','male');

        $sen_f_pro  = $catId('Senior','pro','female');
        $sen_f_semi = $catId('Senior','semi','female');
        $sen_f_am   = $catId('Senior','amateur','female');

        $juv_m_nat  = $catId('Juvenil Nacional','amateur','male');

        // ===== Tercera Federación (18 territorios RFEF, mapeo)
        $terceraByRegion = [
            'GAL' => 'Galicia',
            'AST' => 'Asturias',
            'CANT'=> 'Cantabria',
            'PVA' => 'País Vasco',
            'CAT' => 'Cataluña',
            'CV'  => 'Comunitat Valenciana',
            'MAD' => 'Comunidad de Madrid',
            'CYL' => 'Castilla y León',
            'AND-E' => 'Andalucía Oriental + Melilla',
            'AND-W' => 'Andalucía Occidental + Ceuta',
            'BAL' => 'Illes Balears',
            'CAN' => 'Canarias',
            'MUR' => 'Región de Murcia',
            'EXT' => 'Extremadura',
            'NAV' => 'Navarra',
            'RIO' => 'La Rioja',
            'ARA' => 'Aragón',
            'CLM' => 'Castilla-La Mancha',
        ];
        $terceraRegionMap = [
            'GAL'=>'GAL','AST'=>'AST','CANT'=>'CANT','PVA'=>'PVA','CAT'=>'CAT','CV'=>'CV','MAD'=>'MAD','CYL'=>'CYL',
            'AND-E'=>'AND','AND-W'=>'AND','BAL'=>'BAL','CAN'=>'CAN','MUR'=>'MUR','EXT'=>'EXT','NAV'=>'NAV',
            'RIO'=>'RIO','ARA'=>'ARA','CLM'=>'CLM',
        ];

        // ===== Territoriales Senior Masculino (completo)
        $provAND = ['Almería','Cádiz','Córdoba','Granada','Huelva','Jaén','Málaga','Sevilla'];
        $provCYL = ['Ávila','Burgos','León','Palencia','Salamanca','Segovia','Soria','Valladolid','Zamora'];

        $seniorMaleTerritorial = [
            'AND' => array_merge(
                ['División de Honor Sénior – Occidental','División de Honor Sénior – Oriental'],
                array_map(fn($p)=>"1ª Andaluza Sénior ({$p})", $provAND),
                array_map(fn($p)=>"2ª Andaluza Sénior ({$p})", $provAND),
                array_map(fn($p)=>"3ª Andaluza Sénior ({$p})", $provAND),
                array_map(fn($p)=>"4ª Andaluza Sénior ({$p})", $provAND)
            ),
            'ARA' => ['Regional Preferente','Primera Regional','Segunda Regional','Segunda Regional B','Tercera Regional'],
            'AST' => ['Regional Preferente','Primera Regional','Segunda Regional'],
            'BAL' => [
                'Preferent Mallorca','Primera Regional Mallorca','Segona Regional Mallorca',
                'Preferent Menorca','Primera Regional Menorca',
                'Preferent Eivissa-Formentera','Primera Regional Eivissa-Formentera',
            ],
            'CAN' => [
                'Interinsular Preferente Las Palmas','Primera Regional Las Palmas','Segunda Regional Las Palmas',
                'Interinsular Preferente Tenerife','Primera Regional Tenerife','Segunda Regional Tenerife',
            ],
            'CANT'=> ['Regional Preferente','Primera Regional','Segunda Regional'],
            'CLM' => ['Primera Autonómica Preferente','Primera Autonómica','Segunda Autonómica'],
            'CYL' => array_merge(
                ['Primera División Regional de Aficionados'],
                array_map(fn($p)=>"Provincial de Aficionados – Primera ({$p})", $provCYL),
                array_map(fn($p)=>"Provincial de Aficionados – Segunda ({$p})", $provCYL)
            ),
            'CAT' => ['Lliga Elit','Primera Catalana','Segona Catalana','Tercera Catalana','Quarta Catalana'],
            'CV'  => ['Lliga Comunitat – Nord','Lliga Comunitat – Sud','Primera FFCV','Segona FFCV','Tercera FFCV'],
            'EXT' => ['Primera División Extremeña','Segunda División Extremeña'],
            'GAL' => ['Preferente Galicia – Norte','Preferente Galicia – Sur','Primera Galicia','Segunda Galicia','Tercera Galicia'],
            'MAD' => ['Preferente Aficionados','Primera Aficionados','Segunda Aficionados','Tercera Aficionados'],
            'MUR' => ['Preferente Autonómica','Primera Autonómica','Segunda Autonómica'],
            'NAV' => ['Regional Preferente','Primera Regional','Segunda Regional'],
            'RIO' => ['Regional Preferente','Primera Regional'],
            'PVA' => [
                'Bizkaia – División de Honor','Bizkaia – Preferente','Bizkaia – Primera Regional','Bizkaia – Segunda Regional',
                'Gipuzkoa – División de Honor','Gipuzkoa – Preferente','Gipuzkoa – Primera Regional','Gipuzkoa – Segunda Regional',
                'Araba – División de Honor','Araba – Primera Regional','Araba – Segunda Regional',
            ],
            'CEU' => ['Primera Autonómica Ceuta','Segunda Regional Ceuta'],
            'MEL' => ['Primera Autonómica Melilla','Segunda Regional Melilla'],
        ];

        // ===== Territoriales Senior Femenino (completo)
        $seniorFemaleTerritorial = [
            'AND' => ['Liga Sénior Femenina Andaluza – Occidental','Liga Sénior Femenina Andaluza – Oriental'],
            'ARA' => ['Liga Territorial Femenina'],
            'AST' => ['Liga Femenina de Asturias'],
            'BAL' => ['Liga Autonómica Femenina Balears','Liga Femenina Mallorca','Liga Femenina Menorca','Liga Femenina Eivissa-Formentera'],
            'CAN' => ['Liga Femenina Interinsular Las Palmas','Liga Femenina Interinsular Tenerife'],
            'CANT'=> ['Liga Femenina de Cantabria'],
            'CLM' => ['Liga Regional Femenina CLM'],
            'CYL' => ['Liga Regional Femenina de Castilla y León'],
            'CAT' => ['Preferent Femení','Primera Divisió Femenina','Segona Divisió Femenina'],
            'CV'  => ['Lliga Autonòmica Femenina FFCV'],
            'EXT' => ['Liga Autonómica Femenina Extremadura'],
            'GAL' => ['Liga Autonómica Feminina Galicia'],
            'MAD' => ['Preferente Femenina Madrid'],
            'MUR' => ['Liga Autonómica Femenina Murcia'],
            'NAV' => ['Liga Regional Femenina Navarra'],
            'RIO' => ['Liga Femenina de La Rioja'],
            'PVA' => ['Liga Vasca Femenina'],
            'CEU' => ['Liga Femenina de Ceuta'],
            'MEL' => ['Liga Femenina de Melilla'],
        ];

        // ===== Juvenil Nacional (base autonómica en Liga Nacional)
        $juvenilRegional = ['AND','ARA','AST','BAL','CAN','CANT','CLM','CYL','CAT','CV','EXT','GAL','MAD','MUR','NAV','RIO','PVA'];

        // ===== Siembra
        foreach ($seasons as $scode) {
            $sid = $seasonId($scode);

            // === Profesional masculino (LaLiga)
            $createLeague(['name'=>'LaLiga EA SPORTS', 'category_id'=>$sen_m_pro, 'season_id'=>$sid, 'region_id'=>null]);
            $createLeague(['name'=>'LaLiga Hypermotion', 'category_id'=>$sen_m_pro, 'season_id'=>$sid, 'region_id'=>null]);

            // === Masculino RFEF
            // Primera Federación (2 grupos)
            foreach ([1,2] as $g) {
                $createLeague(['name'=>"Primera Federación – Grupo {$g}", 'category_id'=>$sen_m_semi, 'season_id'=>$sid, 'region_id'=>null]);
            }
            // Segunda Federación (5 grupos)
            foreach (range(1,5) as $g) {
                $createLeague(['name'=>"Segunda Federación – Grupo {$g}", 'category_id'=>$sen_m_semi, 'season_id'=>$sid, 'region_id'=>null]);
            }
            // Tercera Federación (18 territorios)
            foreach ($terceraByRegion as $key=>$label) {
                $createLeague([
                    'name'        => "Tercera Federación – {$label}",
                    'category_id' => $sen_m_am,
                    'season_id'   => $sid,
                    'region_id'   => $regId($terceraRegionMap[$key] ?? null),
                ]);
            }

            // === Femenino nacional
            $createLeague(['name'=>'Liga F', 'category_id'=>$sen_f_pro, 'season_id'=>$sid, 'region_id'=>null]);
            $createLeague(['name'=>'Primera Federación Femenina', 'category_id'=>$sen_f_semi, 'season_id'=>$sid, 'region_id'=>null]);
            // Segunda Federación Femenina (3 grupos)
            foreach (range(1,3) as $g) {
                $createLeague(['name'=>"Segunda Federación Femenina – Grupo {$g}", 'category_id'=>$sen_f_semi, 'season_id'=>$sid, 'region_id'=>null]);
            }
            // Tercera FUTFEM (placeholder 6 grupos nacionales)
            foreach (range(1,6) as $g) {
                $createLeague(['name'=>"Tercera Federación FUTFEM – Grupo {$g}", 'category_id'=>$sen_f_am, 'season_id'=>$sid, 'region_id'=>null]);
            }

            // === Juvenil nacional
            // División de Honor (7 grupos)
            foreach (range(1,7) as $g) {
                $createLeague(['name'=>"División de Honor Juvenil – Grupo {$g}", 'category_id'=>$juv_m_nat, 'season_id'=>$sid, 'region_id'=>null]);
            }
            // Liga Nacional Juvenil (1 por CCAA)
            foreach ($juvenilRegional as $ccaa) {
                $createLeague([
                    'name'        => "Liga Nacional Juvenil – {$ccaa}",
                    'category_id' => $juv_m_nat,
                    'season_id'   => $sid,
                    'region_id'   => $regId($ccaa),
                ]);
            }

            // === Territoriales Senior Masculino (completo)
            foreach ($seniorMaleTerritorial as $ccaa => $names) {
                foreach ($names as $n) {
                    $createLeague([
                        'name'        => "{$n} ({$ccaa})",
                        'category_id' => $sen_m_am,
                        'season_id'   => $sid,
                        'region_id'   => $regId($ccaa),
                    ]);
                }
            }

            // === Territoriales Senior Femenino (completo)
            foreach ($seniorFemaleTerritorial as $ccaa => $names) {
                foreach ($names as $n) {
                    $createLeague([
                        'name'        => "{$n} ({$ccaa})",
                        'category_id' => $sen_f_am,
                        'season_id'   => $sid,
                        'region_id'   => $regId($ccaa),
                    ]);
                }
            }
        }
    }
}
