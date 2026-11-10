<?php

namespace App\Http\Requests;

use App\Models\Service;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $record = $this->route('service');

        return $record ? $this->user()->can('update', $record) : $this->user()->can('create', Service::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('services')->ignore($this->route('service')?->id)],
            'description' => ['required', 'string', 'max:10000'],
            'is_active' => ['required', 'boolean'],

        ];
    }
}
