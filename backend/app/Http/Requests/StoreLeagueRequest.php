<?php

namespace App\Http\Requests;

use App\Models\Competition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeagueRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:150'],
            'type'        => ['required', Rule::in(['official', 'private'])],
            'season_id'   => ['required', 'exists:seasons,id'],
            'visibility'  => ['required', Rule::in(['private','by_link','public'])],

            // Nuevo modelo
            'competition_id' => ['nullable', 'required_if:type,official', 'exists:competitions,id'],
            'group_name'     => ['nullable', 'string', 'max:100'],

            // Legacy (solo si NO hay competition)
            'category_id' => ['nullable', 'required_without:competition_id', 'exists:categories,id'],
            'region_id'   => ['nullable', 'exists:regions,id'],
            'province_id' => ['nullable', 'exists:provinces,id'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $competitionId = $this->input('competition_id');
            if (!$competitionId) return;

            $comp = Competition::find($competitionId);
            if (!$comp) return;

            // si envían legacy, debe coincidir
            if ($this->filled('category_id') && (int)$this->input('category_id') !== (int)$comp->category_id) {
                $v->errors()->add('category_id', 'category_id debe coincidir con la competición seleccionada.');
            }
            if ($this->filled('region_id') && (int)$this->input('region_id') !== (int)$comp->region_id) {
                $v->errors()->add('region_id', 'region_id debe coincidir con la competición seleccionada.');
            }
            if ($this->filled('province_id') && (int)$this->input('province_id') !== (int)$comp->province_id) {
                $v->errors()->add('province_id', 'province_id debe coincidir con la competición seleccionada.');
            }
        });
    }
}
