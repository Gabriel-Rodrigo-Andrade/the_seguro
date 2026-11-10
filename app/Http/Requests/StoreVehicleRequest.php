<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Models\Vehicle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Vehicle::class);
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->license_plate)) {
            $this->merge(['license_plate' => Vehicle::normalizePlate($this->license_plate)]);
        }
    }

    public function rules(): array
    {
        return $this->vehicleRules() + [
            'license_plate' => ['required', 'string', 'regex:/^[A-Z]{3}[0-9][A-Z0-9][0-9]{2}$/', Rule::unique('vehicles')],
            'user_id' => $this->user()->role === UserRole::Administrator
                ? ['required', 'integer', Rule::exists('users', 'id')->where('role', 'cliente')->where('is_active', true)]
                : ['exclude'],
        ];
    }

    protected function vehicleRules(): array
    {
        $maximum = now('America/Sao_Paulo')->year + 1;

        return [
            'brand' => ['required', 'string', 'max:100'],
            'model' => ['required', 'string', 'max:100'],
            'manufacture_year' => ['required', 'integer', 'digits:4', 'between:1886,'.$maximum],
            'model_year' => ['nullable', 'integer', 'digits:4', 'between:1886,'.$maximum],
            'color' => ['nullable', 'string', 'max:50'],
        ];
    }
}
