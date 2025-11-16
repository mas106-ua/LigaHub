<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminMatchLineupRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Autorización fina la haremos en el controlador con canManageLeague()
        return true;
    }

    public function rules(): array
    {
        return [
            // Al menos home o away
            'home' => ['required_without:away', 'array'],
            'away' => ['required_without:home', 'array'],

            // HOME
            'home.formation'  => ['nullable', 'string', 'max:10'],
            'home.coach_name' => ['nullable', 'string', 'max:120'],

            'home.starters'   => ['required_with:home', 'array', 'size:11'],
            'home.starters.*.player_id' => ['required', 'integer', 'min:1'],
            'home.starters.*.shirt'     => ['nullable', 'integer', 'min:1', 'max:99'],
            'home.starters.*.pos'       => ['required', 'in:GK,DF,MF,FW'],

            'home.bench'   => ['nullable', 'array'],
            'home.bench.*.player_id' => ['required', 'integer', 'min:1'],
            'home.bench.*.shirt'     => ['nullable', 'integer', 'min:1', 'max:99'],
            'home.bench.*.pos'       => ['nullable', 'in:GK,DF,MF,FW'],

            // AWAY (mismas reglas)
            'away.formation'  => ['nullable', 'string', 'max:10'],
            'away.coach_name' => ['nullable', 'string', 'max:120'],

            'away.starters'   => ['required_with:away', 'array', 'size:11'],
            'away.starters.*.player_id' => ['required', 'integer', 'min:1'],
            'away.starters.*.shirt'     => ['nullable', 'integer', 'min:1', 'max:99'],
            'away.starters.*.pos'       => ['required', 'in:GK,DF,MF,FW'],

            'away.bench'   => ['nullable', 'array'],
            'away.bench.*.player_id' => ['required', 'integer', 'min:1'],
            'away.bench.*.shirt'     => ['nullable', 'integer', 'min:1', 'max:99'],
            'away.bench.*.pos'       => ['nullable', 'in:GK,DF,MF,FW'],
        ];
    }

    public function messages(): array
    {
        return [
            'home.required_without' => 'Debes enviar la alineación local o visitante.',
            'away.required_without' => 'Debes enviar la alineación local o visitante.',

            'home.starters.size'    => 'El equipo local debe tener exactamente 11 titulares.',
            'away.starters.size'    => 'El equipo visitante debe tener exactamente 11 titulares.',

            'home.starters.*.pos.in' => 'La posición de los titulares locales debe ser GK, DF, MF o FW.',
            'away.starters.*.pos.in' => 'La posición de los titulares visitantes debe ser GK, DF, MF o FW.',
        ];
    }
}
