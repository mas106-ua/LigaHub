<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PrivateLeagueSchedulePreviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Autorización real en el controller (canManageLeague)
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['nullable', 'in:single,double'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.in' => 'El tipo de calendario debe ser single (ida) o double (ida+vuelta).',
        ];
    }
}
