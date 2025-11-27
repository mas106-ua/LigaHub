<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MatchdaySummaryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'number'        => (int) $this->number,
            'label'         => 'Jornada ' . (int) $this->number,
            'matches_count' => (int) ($this->matches_count ?? 0),
            'played_count'  => (int) ($this->played_count ?? 0),
            'date_range'    => [
                'from' => $this->first_date,
                'to'   => $this->last_date,
            ],
        ];
    }
}
