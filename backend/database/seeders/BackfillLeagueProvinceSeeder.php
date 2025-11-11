<?php

namespace Database\Seeders;

use App\Models\League;
use App\Models\Province;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BackfillLeagueProvinceSeeder extends Seeder
{
    protected array $skipTokens = [
        'AND','CAT','BAL','CAN','PVA','Ceuta','Melilla',
        'Occidental','Oriental','Grupo','Nacional',
    ];

    protected function norm(string $s): string
    {
        return (string) Str::of($s)->lower()->ascii()->trim();
    }

    public function run(): void
    {
        $provinces = Province::all();
        $byName = $provinces->keyBy(fn($p) => $this->norm($p->name));

        // alias → nombre en BD
        $aliases = [
            // Andalucía
            'almeria' => 'Almería', 'cadiz' => 'Cádiz', 'cordoba' => 'Córdoba',
            'granada' => 'Granada', 'huelva' => 'Huelva', 'jaen' => 'Jaén',
            'malaga'  => 'Málaga',  'sevilla'=> 'Sevilla',
            // Baleares
            'mallorca' => 'Mallorca', 'menorca' => 'Menorca',
            'eivissa' => 'Eivissa-Formentera', 'ibiza' => 'Eivissa-Formentera', 'formentera' => 'Eivissa-Formentera',
            // Canarias, por si acaso
            'las palmas' => 'Las Palmas', 'tenerife' => 'Santa Cruz de Tenerife',
            'santa cruz de tenerife' => 'Santa Cruz de Tenerife',
            'araba'     => 'Álava',     // nombre en euskera
            'alava'     => 'Álava',     // sin tilde
            'álava'     => 'Álava',     // con tilde (tu normalización lo reduce igual)
            'bizkaia'   => 'Bizkaia',
            'vizcaya'   => 'Bizkaia',   // castellano
            'gipuzkoa'  => 'Gipuzkoa',
            'guipuzcoa' => 'Gipuzkoa',  // castellano
            'guipúzcoa' => 'Gipuzkoa',
        ];

        League::whereNull('province_id')->chunkById(500, function($chunk) use ($byName, $aliases) {
            foreach ($chunk as $league) {
                $assigned = false;
                $name = $league->name;
                $nameNorm = $this->norm($name);

                // 1) Recorremos *todos* los paréntesis en orden
                if (preg_match_all('/\(([^)]+)\)/u', $name, $m)) {
                    foreach ($m[1] as $raw) {
                        $raw = trim($raw);
                        // saltar tokens no-provincias
                        $skip = false;
                        foreach ($this->skipTokens as $tok) {
                            if (Str::contains(Str::upper($raw), Str::upper($tok))) { $skip = true; break; }
                        }
                        if ($skip) continue;

                        $k = $this->norm($raw);
                        if ($byName->has($k)) {
                            $league->province_id = $byName[$k]->id;
                            $league->save();
                            $assigned = true;
                            break;
                        }

                        // probar alias
                        if (isset($aliases[$k])) {
                            $k2 = $this->norm($aliases[$k]);
                            if ($byName->has($k2)) {
                                $league->province_id = $byName[$k2]->id;
                                $league->save();
                                $assigned = true;
                                break;
                            }
                        }
                    }
                }
                if ($assigned) continue;

                // 2) Como fallback, buscar alias en todo el nombre
                foreach ($aliases as $needle => $provName) {
                    if (Str::contains($nameNorm, $this->norm($needle))) {
                        $k = $this->norm($provName);
                        if ($byName->has($k)) {
                            $league->province_id = $byName[$k]->id;
                            $league->save();
                            break;
                        }
                    }
                }
                // Las ligas multi-provincia (p.ej. "Andalucía Occidental")
//              quedarán a NULL, y está bien.
            }
        });
    }
}
