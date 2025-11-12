<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MatchResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'              => (int) $this->id,
            'league_id'       => (int) $this->league_id,
            'matchday_number' => (int) $this->matchday_number,
            'scheduled_at'    => $this->scheduled_at ? (string) $this->scheduled_at : null,
            'status'          => $this->status,
            'venue'           => $this->venue_name ?? null,
            'home_team'       => [
                'id'   => (int) $this->home_team_id,
                'name' => $this->home_team_name,
            ],
            'away_team'       => [
                'id'   => (int) $this->away_team_id,
                'name' => $this->away_team_name,
            ],
            'score'           => [
                'home' => is_null($this->home_goals) ? null : (int) $this->home_goals,
                'away' => is_null($this->away_goals) ? null : (int) $this->away_goals,
            ],
        ];
    }
}
