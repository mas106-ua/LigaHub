<?php

namespace App\Http\Requests;

use App\Models\Competition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLeagueRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'        => ['sometimes', 'string', 'max:150'],
            'type'        => ['sometimes', Rule::in(['official', 'private'])],
            'season_id'   => ['sometimes', 'exists:seasons,id'],
            'visibility'  => ['sometimes', Rule::in(['private','by_link','public'])],

            'competition_id' => ['sometimes', 'nullable', 'exists:competitions,id'],
            'group_name'     => ['sometimes', 'nullable', 'string', 'max:100'],

            // legacy (compat)
            'category_id' => ['sometimes', 'nullable', 'exists:categories,id'],
            'region_id'   => ['sometimes', 'nullable', 'exists:regions,id'],
            'province_id' => ['sometimes', 'nullable', 'exists:provinces,id'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            if (!$this->filled('competition_id')) return;

            $comp = Competition::find($this->input('competition_id'));
            if (!$comp) return;

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
