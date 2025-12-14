<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Recurso para listar competiciones en el panel de administración.
 */
class AdminCompetitionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'        => (int) $this->id,
            'name'      => $this->name,
            'code'      => $this->code,
            'type'      => $this->type,
            'is_active' => (bool) $this->is_active,
            'level'     => $this->level,
            'gender'    => $this->gender,

            'category' => $this->whenLoaded('category', fn () => [
                'id'     => $this->category?->id,
                'name'   => $this->category?->name,
                'level'  => $this->category?->level,
                'gender' => $this->category?->gender,
            ]),

            'region' => $this->whenLoaded('region', fn () => [
                'id'   => $this->region?->id,
                'code' => $this->region?->code,
                'name' => $this->region?->name,
            ]),

            'province' => $this->whenLoaded('province', fn () => [
                'id'        => $this->province?->id,
                'code'      => $this->province?->code,
                'name'      => $this->province?->name,
                'region_id' => $this->province?->region_id,
            ]),

            'leagues_count' => (int) ($this->leagues_count ?? 0),
        ];
    }
}
