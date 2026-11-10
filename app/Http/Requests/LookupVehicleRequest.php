<?php

namespace App\Http\Requests;

use App\Models\Vehicle;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class LookupVehicleRequest extends FormRequest
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
        return ['license_plate' => ['required', 'string', 'regex:/^[A-Z]{3}[0-9][A-Z0-9][0-9]{2}$/']];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json(['code' => 'invalid_plate', 'message' => 'Informe uma placa brasileira válida.'], 422));
    }
}
