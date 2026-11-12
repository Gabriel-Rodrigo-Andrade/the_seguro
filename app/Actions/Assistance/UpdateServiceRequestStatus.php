<?php

namespace App\Actions\Assistance;

use App\Enums\ServiceRequestStatus;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class UpdateServiceRequestStatus
{
    public function handle(User $operator, ServiceRequest $request, string $status, ?string $response): void
    {
        DB::transaction(function () use ($operator, $request, $status, $response) {
            $request = ServiceRequest::whereKey($request->id)->lockForUpdate()->firstOrFail();
            $operator = $operator->fresh();
            if ($status === ServiceRequestStatus::InProgress->value) {
                Gate::forUser($operator)->authorize('take', $request);
                $request->update(['status' => $status, 'assigned_to' => $operator->id]);

                return;
            }
            if ($status !== ServiceRequestStatus::Completed->value) {
                throw ValidationException::withMessages(['status' => 'Transição inválida.']);
            }
            Gate::forUser($operator)->authorize('complete', $request);
            $response = trim($response ?? '');
            if ($response === '') {
                throw ValidationException::withMessages(['response' => 'Informe a resposta do atendimento.']);
            }
            $request->update(['status' => $status, 'response' => $response, 'completed_at' => now()]);
        });
    }
}
