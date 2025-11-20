<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StandingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Es pública; no restringimos aquí.
        return true;
    }

    public function rules(): array
    {
        return [
            'group'    => ['nullable', 'string', 'max:50'],
            'matchday' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
