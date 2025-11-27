<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class TeamsAndFixturesSeeder extends Seeder
{
    public function run(): void
    {
        $dir = database_path('seeders/data');

        // Carga todos los *.php del directorio data
        foreach (glob($dir.'/*.php') as $file) {
            $cfg = require $file;

            // Validación mínima
            if (!isset($cfg['league_name'], $cfg['season_code'], $cfg['teams'])) {
                $this->command->warn("Config incompleta en: {$file}");
                continue;
            }

            $league = DB::table('leagues as l')
                ->join('seasons as s', 's.id', '=', 'l.season_id')
                ->where('l.name', $cfg['league_name'])
                ->where('s.code', $cfg['season_code'])
                ->select('l.*', 's.start_date as season_start')
                ->first();

            if (!$league) {
                $this->command->warn("Liga no encontrada: {$cfg['league_name']} ({$cfg['season_code']})");
                continue;
            }

            $group = $cfg['group_name'] ?? 'Único';

            // 1) Equipos + pivot
            $teamIds = [];
            foreach ($cfg['teams'] as $rawName) {
                $name = trim(preg_replace('/\s+/', ' ', $rawName));
                if (!$name) continue;

                $teamId = DB::table('teams')->where('name', $name)->value('id');
                if (!$teamId) {
                    $teamId = DB::table('teams')->insertGetId([
                        'name'       => $name,
                        'short_name' => mb_substr($name, 0, 20),                        
                        'city'       => null,
                        'crest_url'  => null,
                    ]);
                }
                $teamIds[] = $teamId;

                DB::table('league_teams')->updateOrInsert(
                    ['league_id' => $league->id, 'team_id' => $teamId],
                    ['group_name' => $group]
                );
            }

            $count = count($teamIds);
            $this->command->info("{$cfg['league_name']} ({$cfg['season_code']}): {$count} equipos (grupo: {$group})");

            // 2) Calendario (opcional)
            if (!empty($cfg['fixtures']['enabled']) && $count >= 2) {
                $start = $this->resolveStart(
                    $league->season_start,
                    $cfg['fixtures']['start'] ?? 'first-saturday-of-season',
                    $cfg['fixtures']['kickoff'] ?? [16,0]
                );
                $this->roundRobin($league->id, $teamIds, $start);
                $this->command->info("Calendario generado (ida+vuelta) desde {$start->format('Y-m-d H:i')}");
            }
        }
    }

    private function resolveStart(?string $seasonStart, string $hint, array $hm): Carbon
    {
        $base = Carbon::parse($seasonStart ?? now()->toDateString());
        if ($hint === 'first-saturday-of-season') {
            return $base->copy()->next(Carbon::SATURDAY)->setTime($hm[0], $hm[1]);
        }
        return Carbon::parse($hint)->setTime($hm[0], $hm[1]);
    }

    private function roundRobin(int $leagueId, array $teamIds, Carbon $start): void
    {
        // 1) Limpia anteriores
        DB::table('matches')->where('league_id', $leagueId)->delete();

        // 2) IDA con Berger (lista de rondas con pares [home,away])
        [$firstLegRounds, $homeAwayTrack] = $this->bergerFirstLeg($teamIds);

        // 3) Inserta IDA
        $now  = now();
        $date = $start->copy();
        $R    = count($firstLegRounds); // N-1

        for ($r = 1; $r <= $R; $r++) {
            foreach ($firstLegRounds[$r] as [$home, $away]) {
                [$status, $hg, $ag] = $this->randomStatusAndScore();
                DB::table('matches')->updateOrInsert(
                    ['league_id'=>$leagueId,'matchday_number'=>$r,'home_team_id'=>$home,'away_team_id'=>$away],
                    [
                        'scheduled_at'=>$date->format('Y-m-d H:i:s'),
                        'status'=>$status,'home_goals'=>$hg,'away_goals'=>$ag,
                        'venue_id'=>null,'created_at'=>$now,'updated_at'=>$now
                    ]
                );
            }
            $date = $date->copy()->addWeek();
        }

        // 4) Ordenar 2ª vuelta (restricciones: sin rematch inmediato, gap para J19, derangement suave)
        $orderSecondLeg = $this->buildSecondLegOrder($R);

        // 5) Inserta VUELTA (pares invertidos y jornadas reordenadas)
        //    Construimos una estructura para poder intentar "reparar" rachas H/A después.
        $secondLegRounds = [];
        for ($i = 1; $i <= $R; $i++) {
            $target = $R + $orderSecondLeg[$i]; // Jornada absoluta en 2ª vuelta
            foreach ($firstLegRounds[$i] as [$h, $a]) {
                $secondLegRounds[$target][] = [$a, $h]; // invertimos local/visitante
            }
        }

        // 6) Reparar rachas >2 (si hay) mediante intercambios simples entre jornadas de 2ª vuelta
        $this->repairHomeAwayStreaks($homeAwayTrack, $firstLegRounds, $secondLegRounds);

        // 7) Persistir 2ª vuelta (orden cronológico)
        ksort($secondLegRounds);
        foreach ($secondLegRounds as $absRound => $pairs) {
            foreach ($pairs as [$home, $away]) {
                [$status, $hg, $ag] = $this->randomStatusAndScore();
                DB::table('matches')->updateOrInsert(
                    ['league_id'=>$leagueId,'matchday_number'=>$absRound,'home_team_id'=>$home,'away_team_id'=>$away],
                    [
                        'scheduled_at'=>$date->format('Y-m-d H:i:s'),
                        'status'=>$status,'home_goals'=>$hg,'away_goals'=>$ag,
                        'venue_id'=>null,'created_at'=>$now,'updated_at'=>$now
                    ]
                );
            }
            $date = $date->copy()->addWeek();
        }
    }

    /**
     * Genera IDA con algoritmo de Berger y devuelve:
     *  - $rounds[1..R] = lista de pares [home, away]
     *  - $track[team_id] = string con 'H'/'A' de la ida (para reparar alternancias luego)
     */
    private function bergerFirstLeg(array $teamIds): array
    {
        $list = array_values($teamIds);
        $n = count($list);
        if ($n % 2 === 1) { $list[] = null; $n++; }

        $R     = $n - 1;
        $half  = intdiv($n, 2);
        $rounds = [];
        $track  = []; // seguimiento H/A por equipo en ida

        for ($r = 0; $r < $R; $r++) {
            $pairs = [];
            for ($i = 0; $i < $half; $i++) {
                $t1 = $list[$i];
                $t2 = $list[$n - 1 - $i];
                if ($t1 !== null && $t2 !== null) {
                    // Truco: alternar el emparejamiento del ancla para mejor H/A
                    if ($i === 0 && ($r % 2 === 1)) {
                        $pairs[] = [$t2, $t1];
                        $track[$t2] = ($track[$t2] ?? '') . 'H';
                        $track[$t1] = ($track[$t1] ?? '') . 'A';
                    } else {
                        $pairs[] = [$t1, $t2];
                        $track[$t1] = ($track[$t1] ?? '') . 'H';
                        $track[$t2] = ($track[$t2] ?? '') . 'A';
                    }
                }
            }
            $rounds[$r + 1] = $pairs;

            // Rotación Berger (fija el primero, rota el resto)
            $first = $list[0];
            $rest  = array_slice($list, 1);
            $last  = array_pop($rest);
            array_unshift($rest, $last);
            $list  = array_merge([$first], $rest);
        }

        return [$rounds, $track];
    }

    /**
     * Construye una permutación para la 2ª vuelta cumpliendo:
     *  - Derangement suave (evitar P(i)=i para muchos i)
     *  - No rematch inmediato de J19 → J20 (P(R) > 6)
     *  - Evitar “cercanía” excesiva: |P(i) - i| > 1 para varios i
     */
    private function buildSecondLegOrder(int $R): array
    {
        // slots de la segunda vuelta serán R+1 .. 2R. Mapeamos la ida i -> slot R + P(i).
        $best = range(1, $R);
        for ($attempt = 0; $attempt < 2000; $attempt++) {
            $P = range(1, $R);
            shuffle($P);

            // regla: el retorno de J19 (i=R) debe caer pasada ~6 jornadas de la 2ª vuelta
            if ($P[$R - 1] <= 6) continue;

            // restricción suave: evita que demasiados i cumplan P(i)=i o |P(i)-i|<=1
            $bad = 0;
            for ($i = 1; $i <= $R; $i++) {
                if ($P[$i - 1] === $i || abs($P[$i - 1] - $i) <= 1) $bad++;
            }
            if ($bad > intdiv($R, 3)) continue; // tolera alguno, evita muchos

            return array_combine(range(1, $R), $P);
        }
        // si no encontró perfecta, devuelve la mejor aproximación
        return array_combine(range(1, $R), $best);
    }

    /**
     * Repara rachas >2 (casa o fuera) en toda la temporada
     * intentando swaps entre jornadas de la 2ª vuelta.
     */
    private function repairHomeAwayStreaks(array $homeAwayTrackFirst, array $firstLegRounds, array &$secondLegRounds): void
    {
        // Construye el plan completo H/A por equipo: ida conocida + segunda vuelta provisional
        $R = count($firstLegRounds);
        ksort($secondLegRounds);

        // Mapa rápido de H/A por jornada 2ª vuelta
        $secondHA = []; // [round][teamId] = 'H'|'A'
        foreach ($secondLegRounds as $round => $pairs) {
            foreach ($pairs as [$h, $a]) {
                $secondHA[$round][$h] = 'H';
                $secondHA[$round][$a] = 'A';
            }
        }

        // Función que calcula la mayor racha H/A de un equipo en toda la temporada
        $calcMaxRun = function (int $teamId) use ($homeAwayTrackFirst, $secondHA, $R): int {
            $seq = $homeAwayTrackFirst[$teamId] ?? '';
            for ($round = $R + 1; $round <= 2 * $R; $round++) {
                $seq .= $secondHA[$round][$teamId] ?? '';
            }
            // calcula racha máxima
            $max = 0; $cur = 0; $prev = null;
            for ($i = 0; $i < strlen($seq); $i++) {
                $c = $seq[$i];
                if ($c === $prev) { $cur++; } else { $cur = 1; $prev = $c; }
                if ($cur > $max) $max = $cur;
            }
            return $max;
        };

        // Si detectamos rachas > 2, intentamos intercambiar partidos entre rondas de 2ª vuelta
        // (heurística simple, pocos intentos para no enredar)
        $teams = array_keys($homeAwayTrackFirst);
        for ($pass = 0; $pass < 8; $pass++) {
            $changed = false;

            foreach ($teams as $tid) {
                if ($calcMaxRun($tid) <= 2) continue; // bien

                // busca dos rondas de 2ª vuelta donde este equipo juega, e intenta swap con otra pareja compatible
                $plays = [];
                foreach ($secondLegRounds as $round => $pairs) {
                    foreach ($pairs as $idx => [$h, $a]) {
                        if ($h === $tid || $a === $tid) $plays[] = [$round, $idx, $h, $a];
                    }
                }
                if (count($plays) < 2) continue;

                // muy conservador: intenta intercambiar el partido del equipo en la ronda actual con otro partido de otra ronda
                foreach ($plays as [$rA, $idxA, $hA, $aA]) {
                    foreach ($secondLegRounds as $rB => $pairsB) {
                        if ($rB === $rA) continue;
                        foreach ($pairsB as $idxB => [$hB, $aB]) {
                            // evita choques de equipos o duplicados de jornada
                            if (in_array($hB, [$hA,$aA], true) || in_array($aB, [$hA,$aA], true)) continue;

                            // probar swap virtual
                            $tmp = $secondLegRounds;
                            $tmp[$rA][$idxA] = [$hB,$aB];
                            $tmp[$rB][$idxB] = [$hA,$aA];

                            // recomputa H/A para el equipo afectado y los otros 3
                            $recalcTeams = [$hA,$aA,$hB,$aB];
                            $ok = true;
                            $tmpHA = [];
                            foreach ($tmp as $r => $ps) {
                                foreach ($ps as [$hh,$aa]) {
                                    $tmpHA[$r][$hh] = 'H';
                                    $tmpHA[$r][$aa] = 'A';
                                }
                            }
                            $check = function($id) use ($homeAwayTrackFirst, $tmpHA, $R): int {
                                $seq = $homeAwayTrackFirst[$id] ?? '';
                                for ($r = $R + 1; $r <= 2 * $R; $r++) $seq .= $tmpHA[$r][$id] ?? '';
                                $max=0;$cur=0;$prev=null;
                                for ($i=0;$i<strlen($seq);$i++){ $c=$seq[$i]; if($c===$prev){$cur++;} else {$cur=1;$prev=$c;} if($cur>$max)$max=$cur; }
                                return $max;
                            };
                            foreach ($recalcTeams as $id) {
                                if ($check($id) > 2) { $ok = false; break; }
                            }
                            if (!$ok) continue;

                            // aplica el swap de verdad
                            $secondLegRounds = $tmp;
                            // reconstruye $secondHA para próximos cálculos
                            $secondHA = [];
                            foreach ($secondLegRounds as $round => $pp) {
                                foreach ($pp as [$H,$A]) { $secondHA[$round][$H]='H'; $secondHA[$round][$A]='A'; }
                            }
                            $changed = true;
                            break 3; // vuelve a evaluar rachas
                        }
                    }
                }
            }

            if (!$changed) break;
        }
    }

    private function randomStatusAndScore(): array
    {
        $pool   = ['scheduled','scheduled','scheduled','played','postponed'];
        $status = $pool[array_rand($pool)];
        $hg = $status === 'played' ? rand(0,4) : null;
        $ag = $status === 'played' ? rand(0,4) : null;
        return [$status, $hg, $ag];
    }
}
