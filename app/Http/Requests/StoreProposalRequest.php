<?php

namespace App\Http\Requests;

use App\Models\Proposal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProposalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Proposal::class);
    }

    public function rules(): array
    {
        return ['vehicle_id' => ['required', 'integer', Rule::exists('vehicles', 'id')->where('user_id', $this->user()->id)],
            'plan_id' => ['required', 'integer', Rule::exists('plans', 'id')->where('is_active', true)]];
    }
}
