<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAssistanceStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('take', $this->route('serviceRequest')) || $this->user()->can('complete', $this->route('serviceRequest'));
    }

    public function rules(): array
    {
        return ['status' => ['required', Rule::in(['in_progress', 'completed'])], 'response' => ['required_if:status,completed', 'nullable', 'string', 'max:10000']];
    }
}
