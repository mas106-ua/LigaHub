<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CompetitionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'   => $this->id,
            'name' => $this->name,
            'region' => $this->whenLoaded('region', fn () => [
                'name' => $this->region?->name,
                'code' => $this->region?->code,
            ]),
            'season' => $this->whenLoaded('season', fn () => [
                'code' => $this->season?->code,
            ]),
            'category' => $this->whenLoaded('category', fn () => [
                'name'   => $this->category?->name,
                'level'  => $this->category?->level,
                'gender' => $this->category?->gender,
            ]),
        ];
    }
}
