<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompetitionIndexRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'region'   => ['nullable', 'string', 'max:10'],
            'season'   => ['nullable', 'string', 'max:20'],
            'search'   => ['nullable', 'string', 'max:150'],
            'page'     => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'gender'   => ['nullable','in:male,female,mixed'],        
            'level'    => ['nullable','in:pro,semi,amateur'],          
        ];
    }
    public function authorize(): bool { return true; }
}
