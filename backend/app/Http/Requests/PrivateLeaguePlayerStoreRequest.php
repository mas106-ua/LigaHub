<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PrivateLeaguePlayerStoreRequest extends FormRequest
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
            'full_name'      => ['required', 'string', 'max:150'],
            'position'       => ['nullable', Rule::in(['GK','DF','MF','FW','NA'])],
            'date_of_birth'  => ['nullable', 'date'],
            'doc_number'     => ['nullable', 'string', 'max:50'],

            'team_id'        => ['required', 'integer', 'exists:teams,id'],
            'shirt_number'   => ['nullable', 'integer', 'min:0', 'max:99'],
        ];
    }
}
