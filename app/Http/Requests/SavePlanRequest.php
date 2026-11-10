<?php

namespace App\Http\Requests;

use App\Models\Plan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SavePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        $record = $this->route('plan');

        return $record ? $this->user()->can('update', $record) : $this->user()->can('create', Plan::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('plans')->ignore($this->route('plan')?->id)],
            'description' => ['required', 'string', 'max:10000'],
            'is_active' => ['required', 'boolean'],
            'annual_price' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:9999999999.99'],
            'services' => ['required', 'array', 'min:1'],
            'services.*' => ['required', 'integer', 'distinct', Rule::exists('services', 'id')->where('is_active', true)],
        ];
    }
}
