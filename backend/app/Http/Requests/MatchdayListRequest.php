<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MatchdayListRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'group' => ['nullable', 'string', 'max:50'],
        ];
    }
}
