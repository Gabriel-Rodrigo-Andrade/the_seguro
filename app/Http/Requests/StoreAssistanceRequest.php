<?php

namespace App\Http\Requests;

use App\Models\ServiceRequest;
use Illuminate\Foundation\Http\FormRequest;

class StoreAssistanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', ServiceRequest::class);
    }

    public function rules(): array
    {
        return ['insurance_contract_id' => ['required', 'integer'], 'service_id' => ['required', 'integer'], 'description' => ['required', 'string', 'max:10000'], 'location' => ['required', 'string', 'max:255']];
    }
}
