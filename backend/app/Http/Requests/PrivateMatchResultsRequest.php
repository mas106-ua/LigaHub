<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PrivateMatchResultsRequest extends FormRequest
{
    public function authorize(): bool
    {
        // permisos en controller (canManageLeague)
        return true;
    }

    public function rules(): array
    {
        return [
            'matches'               => ['required', 'array', 'min:1'],

            'matches.*.id'          => ['required', 'integer', 'exists:matches,id'],
            'matches.*.home_goals'  => ['nullable', 'integer', 'min:0', 'max:20'],
            'matches.*.away_goals'  => ['nullable', 'integer', 'min:0', 'max:20'],
            'matches.*.status'      => ['required', 'in:scheduled,played,postponed,canceled'],
        ];
    }

    public function messages(): array
    {
        return [
            'matches.required'          => 'Debe enviarse al menos un partido.',
            'matches.*.id.required'     => 'Cada partido debe tener un id.',
            'matches.*.id.exists'       => 'Alguno de los partidos no existe.',
            'matches.*.status.required' => 'Cada partido debe tener un estado.',
        ];
    }
}
