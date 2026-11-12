<?php

namespace App\Http\Requests;

use App\Enums\ProposalStatus;
use App\Enums\ServiceRequestStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DashboardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_active === true;
    }

    public function rules(): array
    {
        return ['date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', Rule::when($this->filled('date_from'), 'after_or_equal:date_from')],
            'proposal_status' => ['nullable', Rule::enum(ProposalStatus::class)],
            'assistance_status' => ['nullable', Rule::enum(ServiceRequestStatus::class)]];
    }
}
