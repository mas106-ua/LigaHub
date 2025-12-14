<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminCompetitionIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        // La autorización real la gestiona el middleware (role:superadmin,admin)
        return true;
    }

    public function rules(): array
    {
        return [
            'search'   => ['nullable', 'string', 'max:150'],
            'level'    => ['nullable', 'in:pro,semi,amateur'],
            'gender'   => ['nullable', 'in:male,female,mixed'],
            'category' => ['nullable', 'string', 'max:50'],
            'region'   => ['nullable', 'string', 'max:10'],
            'province' => ['nullable', 'string', 'max:10'],
            'page'     => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
