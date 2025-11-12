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
        DB::table('matches')->where('league_id', $leagueId)->delete();

        $teams = array_values($teamIds);
        $n = count($teams);
        if ($n % 2 === 1) { $teams[] = null; $n++; }

        $rounds = $n - 1;
        $h1 = array_slice($teams, 0, $n/2);
        $h2 = array_slice($teams, $n/2);

        $date = $start->copy();
        $md   = 1;

        $make = function($mdNum, $home, $away, $d) use ($leagueId) {
            if (!$home || !$away) return;
            $pool = ['scheduled','scheduled','scheduled','played','postponed'];
            $status = $pool[array_rand($pool)];
            $hg = $status === 'played' ? rand(0,4) : null;
            $ag = $status === 'played' ? rand(0,4) : null;

            DB::table('matches')->updateOrInsert(
                ['league_id'=>$leagueId,'matchday_number'=>$mdNum,'home_team_id'=>$home,'away_team_id'=>$away],
                ['scheduled_at'=>$d->copy(),'status'=>$status,'home_goals'=>$hg,'away_goals'=>$ag,'venue_id'=>null,'created_at'=>now(),'updated_at'=>now()]
            );
        };

        // Ida
        for ($r=0; $r<$rounds; $r++) {
            for ($i=0; $i<$n/2; $i++) $make($md, $h1[$i], $h2[$i], $date);
            $this->rotate($h1, $h2);
            $date = $date->copy()->addWeek();
            $md++;
        }
        // Vuelta
        for ($r=0; $r<$rounds; $r++) {
            for ($i=0; $i<$n/2; $i++) $make($md, $h2[$i], $h1[$i], $date);
            $this->rotate($h1, $h2);
            $date = $date->copy()->addWeek();
            $md++;
        }
    }

    private function rotate(array &$a, array &$b): void
    {
        $last = array_pop($a);
        array_unshift($b, $last);
        $m = array_pop($b);
        array_splice($a, 1, 0, [$m]);
    }
}
