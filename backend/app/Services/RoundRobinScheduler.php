<?php

namespace App\Services;

class RoundRobinScheduler
{
    /**
     * Devuelve pairings por jornada:
     * [
     *   1 => [[homeId, awayId], ...],
     *   2 => ...
     * ]
     *
     * - Impar: añade BYE (null) y se omiten emparejamientos con null.
     * - rounds=2: segunda vuelta invierte local/visitante y añade offset.
     *
     * IMPORTANTE: Determinismo depende de que $teamIds venga ordenado.
     */
    public function build(array $teamIds, int $rounds = 1): array
    {
        $teamIds = array_values($teamIds);

        $firstLeg = $this->bergerFirstLeg($teamIds);

        if ($rounds <= 1) {
            return $firstLeg;
        }

        $baseRounds = count($firstLeg);
        $secondLeg = [];

        for ($md = 1; $md <= $baseRounds; $md++) {
            $md2 = $baseRounds + $md;
            $secondLeg[$md2] = [];

            foreach ($firstLeg[$md] as [$home, $away]) {
                $secondLeg[$md2][] = [$away, $home];
            }
        }

        return $firstLeg + $secondLeg;
    }

    /**
     * Berger / Circle method (ida).
     */
    private function bergerFirstLeg(array $teamIds): array
    {
        $list = array_values($teamIds);

        if (count($list) % 2 === 1) {
            $list[] = null; // BYE
        }

        $n = count($list);
        $roundsCount = $n - 1;
        $half = intdiv($n, 2);

        $rounds = [];

        for ($r = 0; $r < $roundsCount; $r++) {
            $pairs = [];

            for ($i = 0; $i < $half; $i++) {
                $a = $list[$i];
                $b = $list[$n - 1 - $i];

                if ($a === null || $b === null) {
                    continue;
                }

                // Balanceo determinista: alterna el "ancla"
                if ($i === 0 && ($r % 2 === 1)) {
                    $pairs[] = [$b, $a];
                } else {
                    $pairs[] = [$a, $b];
                }
            }

            $rounds[$r + 1] = $pairs;

            // Rotación: fijo el primero, roto el resto
            $fixed = $list[0];
            $rest  = array_slice($list, 1);
            $last  = array_pop($rest);
            array_unshift($rest, $last);
            $list = array_merge([$fixed], $rest);
        }

        return $rounds;
    }
}
