<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PrivateLeagueSchedulePublishRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['nullable', 'in:single,double'],
        ];
    }
}
