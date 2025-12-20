<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CompetitionResource extends JsonResource
{
    public function toArray($request): array
    {
        // League -> Competition (nuevo modelo)
        $competition   = $this->competition ?? null;

        // Preferimos datos de Competition; si no hay, usamos los de League (compatibilidad)
        $regionModel   = $competition?->region   ?? $this->region;
        $categoryModel = $competition?->category ?? $this->category;
        $seasonModel   = $this->season; // siempre viene de League

        return [
            'id'   => $this->id,
            'name' => $this->name,

            'region' => $regionModel ? [
                'name' => $regionModel->name,
                'code' => $regionModel->code,
            ] : null,

            'season' => $seasonModel ? [
                'code' => $seasonModel->code,
            ] : null,

            'category' => $categoryModel ? [
                'name'   => $categoryModel->name,
                'level'  => $categoryModel->level,
                'gender' => $categoryModel->gender,
            ] : null,
        ];
    }
}
