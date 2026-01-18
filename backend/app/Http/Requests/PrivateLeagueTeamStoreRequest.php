<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PrivateLeagueTeamStoreRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    protected function prepareForValidation(): void
    {
        if ($this->has('name')) {
            $this->merge(['name' => trim((string)$this->input('name'))]);
        }
        if ($this->has('short_name')) {
            $this->merge(['short_name' => trim((string)$this->input('short_name'))]);
        }
        if ($this->has('group_name')) {
            $this->merge(['group_name' => trim((string)$this->input('group_name'))]);
        }
    }

    public function rules(): array
    {
        return [
            'name'       => ['required', 'string', 'max:120'],
            'short_name' => ['nullable', 'string', 'max:20'],
            'city'       => ['nullable', 'string', 'max:120'],
            'crest_url'  => ['nullable', 'string', 'max:255'],
            'group_name' => ['nullable', 'string', 'max:50'],
        ];
    }
}
