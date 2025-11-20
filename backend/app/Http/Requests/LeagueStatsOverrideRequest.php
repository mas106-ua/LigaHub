<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LeagueStatsOverrideRequest extends FormRequest
{
    public function authorize(): bool
    {
        // El control fino lo hacemos en el controlador con canManageLeague
        return true;
    }

    public function rules(): array
    {
        return [
            'items'                           => ['required', 'array'],
            'items.*.player_id'               => ['required', 'integer', 'exists:players,id'],
            'items.*.goals_delta'             => ['nullable', 'integer', 'between:-20,20'],
            'items.*.assists_delta'           => ['nullable', 'integer', 'between:-20,20'],
            'items.*.yellow_cards_delta'      => ['nullable', 'integer', 'between:-20,20'],
            'items.*.red_cards_delta'         => ['nullable', 'integer', 'between:-20,20'],
            'items.*.reason'                  => ['nullable', 'string', 'max:255'],
        ];
    }
}
