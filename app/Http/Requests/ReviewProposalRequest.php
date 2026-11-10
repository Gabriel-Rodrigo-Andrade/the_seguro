<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewProposalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('review', $this->route('proposal'));
    }

    public function rules(): array
    {
        return ['decision' => ['required', Rule::in(['approved', 'rejected'])],
            'review_notes' => ['required_if:decision,rejected', 'nullable', 'string', 'max:5000']];
    }
}
