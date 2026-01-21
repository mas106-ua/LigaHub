<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PrivateLeaguePlayerUpdateRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    protected function prepareForValidation(): void
    {
        if ($this->has('full_name')) {
            $this->merge(['full_name' => trim((string) $this->input('full_name'))]);
        }
    }

    public function rules(): array
    {
        return [
            'full_name'      => ['sometimes', 'required', 'string', 'max:150'],
            'position'       => ['sometimes', 'nullable', Rule::in(['GK','DF','MF','FW','NA'])],
            'date_of_birth'  => ['sometimes', 'nullable', 'date'],
            'doc_number'     => ['sometimes', 'nullable', 'string', 'max:50'],

            'team_id'        => ['sometimes', 'nullable', 'integer', 'exists:teams,id'],
            'shirt_number'   => ['sometimes', 'nullable', 'integer', 'min:0', 'max:99'],
        ];
    }
}
