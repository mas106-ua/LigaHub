<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Recurso para listar ediciones (leagues) de una Competition en el panel admin.
 */
class AdminCompetitionLeagueResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // season_code viene del select (join), y season relación si está cargada
        $seasonCode = $this->season_code ?? ($this->season?->code);

        return [
            'id'         => (int) $this->id,
            'name'       => $this->name,
            'season'     => $seasonCode,
            'season_id'  => $this->season_id,
            'group_name' => $this->group_name,

            'teams_count'          => (int) ($this->teams_count ?? 0),
            'matches_total_count'  => (int) ($this->matches_total_count ?? 0),
            'matches_played_count' => (int) ($this->matches_played_count ?? 0),
        ];
    }
}
