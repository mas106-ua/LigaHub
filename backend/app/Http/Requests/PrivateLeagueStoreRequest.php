<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PrivateLeagueStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Formulario mínimo
            'name' => ['required', 'string', 'max:150'],

            // Opcional: asociar una temporada existente
            'season_id' => ['nullable', 'integer', 'exists:seasons,id'],

            // Opcional: permitir elegir visibilidad desde el inicio
            'visibility' => ['nullable', Rule::in(['private', 'by_link', 'public'])],

            // Campos no relevantes para privadas (opcionales, por si algún día los usas)
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'region_id'   => ['nullable', 'integer', 'exists:regions,id'],
            'province_id' => ['nullable', 'integer', 'exists:provinces,id'],
        ];
    }
}
