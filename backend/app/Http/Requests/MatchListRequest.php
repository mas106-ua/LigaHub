<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MatchListRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'group'  => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'in:scheduled,played,postponed,canceled'],
        ];
    }
}
