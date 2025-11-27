<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MatchDetailResource extends JsonResource
{
    private ?string $groupName;
    private $events;

    public function __construct($resource, ?string $groupName = null, $events = [])
    {
        parent::__construct($resource);
        $this->groupName = $groupName;
        $this->events    = $events;
    }

    public function toArray($request): array
    {
        $m = $this->resource;

        return [
            'id'               => $m->id,
            'matchday_number'  => $m->matchday_number,
            'scheduled_at'     => optional($m->scheduled_at)->toDateTimeString(),
            'status'           => $m->status,
            'score'            => ['home' => $m->home_goals, 'away' => $m->away_goals],
            'league' => [
                'id'       => $m->league?->id,
                'name'     => $m->league?->name,
                'season'   => $m->league?->season?->code,
                'category' => $m->league?->category?->name,
                'region'   => $m->league?->region?->code,
                'group'    => $this->groupName ?? 'Único',
            ],
            'home_team' => [
                'id'         => $m->homeTeam?->id,
                'name'       => $m->homeTeam?->name,
                'short_name' => $m->homeTeam?->short_name,
                'crest_url'  => $m->homeTeam?->crest_url,
            ],
            'away_team' => [
                'id'         => $m->awayTeam?->id,
                'name'       => $m->awayTeam?->name,
                'short_name' => $m->awayTeam?->short_name,
                'crest_url'  => $m->awayTeam?->crest_url,
            ],
            'venue' => $m->venue_id ? ['id' => $m->venue_id] : null,
            'notes' => $m->notes,
            'events' => collect($this->events)->map(function ($e) {
                return [
                    'id'     => $e->id,
                    'minute' => $e->minute,
                    'type'   => $e->type,   // goal, yellow, red, sub_in, etc.
                    'side'   => $e->side,   // 'home' / 'away'

                    // Usaremos "detail" en el frontend
                    'detail' => $e->description,

                    'team'   => [
                        'id'   => $e->team_id,
                        'name' => $e->team_name,
                    ],

                    'player' => $e->player_id
                        ? ['id' => $e->player_id, 'name' => $e->player_name]
                        : null,

                    'related_player' => $e->related_player_id
                        ? ['id' => $e->related_player_id, 'name' => $e->related_player_name]
                        : null,
                ];
            })->values(),
        ];
    }
}
