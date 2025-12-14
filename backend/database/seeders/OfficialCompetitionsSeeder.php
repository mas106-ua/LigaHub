<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * OfficialCompetitionsSeeder (Sprint 3 refactor)
 *
 * - Crea competiciones oficiales usando el nuevo modelo:
 *   Competition (estable) 1..N League (ediciones por temporada y grupo)
 * - NOTA: parte de nombres y estructuras simplificadas (especialmente territoriales/juveniles)
 *   para el TFG, sin garantizar 100% fidelidad federativa en todas las CCAA.
 */
class OfficialCompetitionsSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // ===== Helpers
        $regId = fn(?string $code) => $code
            ? DB::table('regions')->where('code', $code)->value('id')
            : null;

        $seasonId = fn(string $code) =>
            DB::table('seasons')->where('code', $code)->value('id');

        $catId = fn(string $name, string $level, string $gender) =>
            DB::table('categories')->where(compact('name','level','gender'))->value('id');

        /**
         * Analiza un nombre de liga para separar la competición base y el nombre de grupo.
         * Ej:
         * - "Primera Federación – Grupo 1" => ["Primera Federación", "Grupo 1"]
         * - "Lliga Comunitat Juvenil – Grup Nord" => ["Lliga Comunitat Juvenil", "Grup Nord"]
         */
        $parseLeagueName = function (string $name): array {
            $base  = trim($name);
            $group = null;

            // Split por guion largo con espacios
            if (preg_match('/^(.+?)\s+–\s+(.+)$/u', $name, $m)) {
                $base  = trim($m[1]);
                $group = trim($m[2]);
            }

            return [$base, $group ?: null];
        };

        /**
         * Crea/actualiza una Competition.
         * IMPORTANTE:
         * - Para competiciones territoriales (con region_id) el code añade el sufijo de región
         *   para evitar colisiones entre nombres genéricos (p.ej. "Regional Preferente").
         */
        $createCompetition = function (
            string $baseName,
            int $categoryId,
            string $level,
            string $gender,
            ?int $regionId = null
        ) use ($now) {
            $max = 50;

            $baseSlug = Str::slug($baseName);

            $regionCode = null;
            if ($regionId) {
                $regionCode = DB::table('regions')->where('id', $regionId)->value('code');
            }

            $suffix = $regionCode ? '-' . Str::lower($regionCode) : '';
            $code   = $baseSlug . $suffix;

            // Si se pasa de 50, truncamos y añadimos hash estable para mantener unicidad
            if (strlen($code) > $max) {
                $hash = substr(md5($baseName.'|'.$categoryId.'|'.$level.'|'.$gender.'|'.($regionId ?? '')), 0, 8);

                $prefixLen = $max - strlen($suffix) - 1 - strlen($hash); // "-" + hash + suffix
                $prefixLen = max(1, $prefixLen);

                $prefix = rtrim(substr($baseSlug, 0, $prefixLen), '-');

                $code = $prefix . '-' . $hash . $suffix;

                // Seguridad extra (por si acaso)
                $code = substr($code, 0, $max);
            }

            $defaults = [
                'name'        => $baseName,
                'code'        => $code,
                'category_id' => $categoryId,
                'level'       => $level,
                'gender'      => $gender,
                'region_id'   => $regionId,
                'province_id' => null,
                'type'        => 'official',
                'is_active'   => true,
                'created_at'  => $now,
                'updated_at'  => $now,
            ];

            DB::table('competitions')->updateOrInsert(['code' => $code], $defaults);

            return DB::table('competitions')->where('code', $code)->value('id');
        };

        /**
         * Crea/actualiza una League y la enlaza con Competition + Season.
         * Clave de edición: competition_id + season_id + group_name.
         */
        $createLeague = function (array $attrs) use ($now, $parseLeagueName, $createCompetition) {
            [$baseName, $groupName] = $parseLeagueName($attrs['name']);

            $categoryId = $attrs['category_id'];
            $catRow = DB::table('categories')->where('id', $categoryId)->first(['level','gender']);
            $level  = $catRow->level  ?? 'amateur';
            $gender = $catRow->gender ?? 'mixed';

            $regionId = $attrs['region_id'] ?? null;
            $competitionId = $createCompetition($baseName, $categoryId, $level, $gender, $regionId);

            $where = [
                'competition_id' => $competitionId,
                'season_id'      => $attrs['season_id'],
                'group_name'     => $groupName,
            ];
            $defaults = array_merge([
                'name'           => $attrs['name'],
                'type'           => 'official',
                'visibility'     => 'public',
                'access_uuid'    => null,
                'owner_user_id'  => null,
                'is_active'      => true,
                'created_at'     => $now,
                'updated_at'     => $now,
                'competition_id' => $competitionId,
                'group_name'     => $groupName,
            ], $attrs);

            DB::table('leagues')->updateOrInsert($where, $defaults);
        };

        // ===== Season codes a sembrar
        $seasons = ['2021/22','2022/23','2023/24','2024/25','2025/26'];

        // ===== Categorías base
        $sen_m_pro  = $catId('Senior','pro','male');
        $sen_m_semi = $catId('Senior','semi','male');
        $sen_m_am   = $catId('Senior','amateur','male');

        $sen_f_pro  = $catId('Senior','pro','female');
        $sen_f_semi = $catId('Senior','semi','female');
        $sen_f_am   = $catId('Senior','amateur','female');

        // Juvenil
        $juv_m_nat  = $catId('Juvenil Nacional','amateur','male');
        $juv_m_terr = $catId('Juvenil','amateur','male');
        $juv_f      = $catId('Juvenil','amateur','female');

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

        // ===== Territoriales Senior (resumen: se mantienen igual que BE-REF-06)
        $provAND = ['Almería','Cádiz','Córdoba','Granada','Huelva','Jaén','Málaga','Sevilla'];
        $provCYL = ['Ávila','Burgos','León','Palencia','Salamanca','Segovia','Soria','Valladolid','Zamora'];

        $seniorMaleTerritorial = [
            'AND' => array_merge(
                ['División de Honor Sénior – Occidental','División de Honor Sénior – Oriental'],
                array_map(fn($p)=>"1ª Andaluza Sénior – {$p}", $provAND),
                array_map(fn($p)=>"2ª Andaluza Sénior – {$p}", $provAND),
                array_map(fn($p)=>"3ª Andaluza Sénior – {$p}", $provAND),
                array_map(fn($p)=>"4ª Andaluza Sénior – {$p}", $provAND)
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
                array_map(fn($p)=>"Provincial de Aficionados – Primera – {$p}", $provCYL),
                array_map(fn($p)=>"Provincial de Aficionados – Segunda – {$p}", $provCYL)
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

        // ===== Juvenil territorial masculino (máxima autonómica, simplificado)
        $juvenilMaleTerritorial = [
            'AND' => ['Liga Juvenil Andaluza – Occidental','Liga Juvenil Andaluza – Oriental'],
            'ARA' => ['Juvenil Preferente Aragón'],
            'AST' => ['Primera Juvenil Asturias'],
            'BAL' => ['Lliga Juvenil Autonòmica Balear'],
            'CAN' => ['Juvenil Preferente Las Palmas','Juvenil Preferente Tenerife'],
            'CANT'=> ['Primera Juvenil Cantabria'],
            'CLM' => ['Juvenil Preferente Castilla-La Mancha – Grupo 1','Juvenil Preferente Castilla-La Mancha – Grupo 2'],
            'CYL' => ['Liga Juvenil Castilla y León'],
            'CAT' => ['Preferent Juvenil Cataluña'],
            'MAD' => ['Primera Autonómica Juvenil Madrid'],
            // CV cambia en 2025/26 (Lliga Comunitat con 2 grupos). Para temporadas previas usamos un nombre genérico.
            'CV'  => [],
            'EXT' => ['Liga Juvenil Extremadura'],
            'GAL' => ['Liga Gallega Juvenil'],
            'NAV' => ['Liga Juvenil Navarra'],
            'RIO' => ['Liga Juvenil Rioja'],
            'PVA' => ['Liga Vasca Juvenil – Euskal Liga'],
            'MUR' => ['Liga Juvenil Murcia'],
            'CEU' => ['Liga Juvenil Ceuta'],
            'MEL' => ['Liga Juvenil Melilla'],
        ];

        // ===== Juvenil territorial femenino (sub-19/sub-18/cadete, simplificado)
        $juvenilFemaleTerritorial = [
            'CAT' => ['Liga Juvenil-Cadet Femenina Cataluña'],
            'CV'  => ['Lliga Valenta Juvenil-Cadet Comunitat Valenciana'],
            'MAD' => ['Liga Juvenil Femenina Madrid'],
            'GAL' => ['Liga Autonómica Femenina Sub-18 Galicia'],
            'AST' => ['Liga Juvenil Femenina Asturias'],
            'MUR' => ['Liga Femenina Juvenil/Cadet Región de Murcia'],
            'CLM' => ['Liga Regional Femenina Sub-18 Castilla-La Mancha'],
            'ARA' => ['Liga Juvenil Femenina Aragón'],
            'NAV' => ['Liga Juvenil Femenina Navarra'],
            'CAN' => ['Liga Juvenil Femenina Canarias'],
        ];

        // ===== Juvenil Nacional (ya existente: Liga Nacional Juvenil por CCAA)
        $juvenilRegional = ['AND','ARA','AST','BAL','CAN','CANT','CLM','CYL','CAT','CV','EXT','GAL','MAD','MUR','NAV','RIO','PVA'];

        // ===== Siembra por temporada
        foreach ($seasons as $scode) {
            $sid = $seasonId($scode);

            // === Senior masculino (LaLiga)
            $createLeague(['name'=>'LaLiga EA SPORTS', 'category_id'=>$sen_m_pro, 'season_id'=>$sid, 'region_id'=>null]);
            $createLeague(['name'=>'LaLiga Hypermotion', 'category_id'=>$sen_m_pro, 'season_id'=>$sid, 'region_id'=>null]);

            // === Masculino RFEF
            foreach ([1,2] as $g) {
                $createLeague(['name'=>"Primera Federación – Grupo {$g}", 'category_id'=>$sen_m_semi, 'season_id'=>$sid, 'region_id'=>null]);
            }
            foreach (range(1,5) as $g) {
                $createLeague(['name'=>"Segunda Federación – Grupo {$g}", 'category_id'=>$sen_m_semi, 'season_id'=>$sid, 'region_id'=>null]);
            }
            foreach ($terceraByRegion as $key=>$label) {
                $createLeague([
                    'name'        => "Tercera Federación – {$label}",
                    'category_id' => $sen_m_semi,
                    'season_id'   => $sid,
                    'region_id'   => $regId($terceraRegionMap[$key] ?? null),
                ]);
            }

            // === Copas nacionales masculinas
            $createLeague(['name'=>'Copa del Rey', 'category_id'=>$sen_m_pro, 'season_id'=>$sid, 'region_id'=>null]);
            $createLeague(['name'=>'Supercopa de España', 'category_id'=>$sen_m_pro, 'season_id'=>$sid, 'region_id'=>null]);
            $createLeague(['name'=>'Copa Federación', 'category_id'=>$sen_m_am, 'season_id'=>$sid, 'region_id'=>null]);

            // === Senior femenino
            $createLeague(['name'=>'Liga F', 'category_id'=>$sen_f_pro, 'season_id'=>$sid, 'region_id'=>null]);
            $createLeague(['name'=>'Primera Federación Femenina', 'category_id'=>$sen_f_semi, 'season_id'=>$sid, 'region_id'=>null]);
            foreach (range(1,3) as $g) {
                $createLeague(['name'=>"Segunda Federación Femenina – Grupo {$g}", 'category_id'=>$sen_f_semi, 'season_id'=>$sid, 'region_id'=>null]);
            }

            // Tercera FUTFEM: 6 grupos hasta 2024/25, 18 grupos desde 2025/26
            $futfemGroupCount = ($scode === '2025/26') ? 18 : 6;
            $futfemRegions    = array_keys($terceraByRegion);
            for ($g = 1; $g <= $futfemGroupCount; $g++) {
                $regionId = null;
                if ($futfemGroupCount >= 18) {
                    $key = $futfemRegions[$g - 1] ?? null;
                    $regionId = $key ? $regId($terceraRegionMap[$key] ?? null) : null;
                }
                $createLeague([
                    'name'        => "Tercera Federación FUTFEM – Grupo {$g}",
                    'category_id' => $sen_f_semi,
                    'season_id'   => $sid,
                    'region_id'   => $regionId,
                ]);
            }

            // Copas femeninas
            $createLeague(['name'=>'Copa de la Reina', 'category_id'=>$sen_f_pro, 'season_id'=>$sid, 'region_id'=>null]);
            $createLeague(['name'=>'Supercopa de España Femenina', 'category_id'=>$sen_f_pro, 'season_id'=>$sid, 'region_id'=>null]);

            // === Juvenil nacional masculino
            foreach (range(1,7) as $g) {
                $createLeague(['name'=>"División de Honor Juvenil – Grupo {$g}", 'category_id'=>$juv_m_nat, 'season_id'=>$sid, 'region_id'=>null]);
            }
            foreach ($juvenilRegional as $ccaa) {
                $createLeague([
                    'name'        => "Liga Nacional Juvenil – {$ccaa}",
                    'category_id' => $juv_m_nat,
                    'season_id'   => $sid,
                    'region_id'   => $regId($ccaa),
                ]);
            }

            // Copas juveniles masculinas (nacional)
            $createLeague(['name'=>'Copa de Campeones Juvenil', 'category_id'=>$juv_m_nat, 'season_id'=>$sid, 'region_id'=>null]);
            $createLeague(['name'=>'Copa del Rey Juvenil', 'category_id'=>$juv_m_nat, 'season_id'=>$sid, 'region_id'=>null]);

            // Juvenil territorial masculino (máxima autonómica)
            foreach ($juvenilMaleTerritorial as $ccaa => $names) {
                // Caso especial CV en 2025/26: Lliga Comunitat (dos grupos)
                if ($ccaa === 'CV') {
                    if ($scode === '2025/26') {
                        $createLeague(['name'=>'Lliga Comunitat Juvenil – Grup Nord', 'category_id'=>$juv_m_terr, 'season_id'=>$sid, 'region_id'=>$regId('CV')]);
                        $createLeague(['name'=>'Lliga Comunitat Juvenil – Grup Sud',  'category_id'=>$juv_m_terr, 'season_id'=>$sid, 'region_id'=>$regId('CV')]);
                    } else {
                        // Placeholder genérico para temporadas anteriores
                        $createLeague(['name'=>'Liga Juvenil Autonómica Comunitat Valenciana', 'category_id'=>$juv_m_terr, 'season_id'=>$sid, 'region_id'=>$regId('CV')]);
                    }
                    continue;
                }

                foreach ($names as $n) {
                    $createLeague([
                        'name'        => $n,
                        'category_id' => $juv_m_terr,
                        'season_id'   => $sid,
                        'region_id'   => $regId($ccaa),
                    ]);
                }
            }

            // === Juvenil femenino
            // Nacional (formato copa)
            $createLeague(['name'=>'Torneo Juvenil Femenino RFEF', 'category_id'=>$juv_f, 'season_id'=>$sid, 'region_id'=>null]);

            // Territorial (simplificado)
            foreach ($juvenilFemaleTerritorial as $ccaa => $names) {
                foreach ($names as $n) {
                    $createLeague([
                        'name'        => $n,
                        'category_id' => $juv_f,
                        'season_id'   => $sid,
                        'region_id'   => $regId($ccaa),
                    ]);
                }
            }

            // === Senior territorial masculino
            foreach ($seniorMaleTerritorial as $ccaa => $names) {
                foreach ($names as $n) {
                    $createLeague([
                        'name'        => $n,
                        'category_id' => $sen_m_am,
                        'season_id'   => $sid,
                        'region_id'   => $regId($ccaa),
                    ]);
                }
            }

            // === Senior territorial femenino
            foreach ($seniorFemaleTerritorial as $ccaa => $names) {
                foreach ($names as $n) {
                    $createLeague([
                        'name'        => $n,
                        'category_id' => $sen_f_am,
                        'season_id'   => $sid,
                        'region_id'   => $regId($ccaa),
                    ]);
                }
            }
        }
    }
}
