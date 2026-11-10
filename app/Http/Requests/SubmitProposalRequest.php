<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitProposalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('submit', $this->route('proposal'));
    }

    public function rules(): array
    {
        return ['quote_token' => ['required', 'string', 'max:8192']];
    }
}
