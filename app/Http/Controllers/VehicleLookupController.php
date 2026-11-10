<?php

namespace App\Http\Controllers;

use App\Http\Requests\LookupVehicleRequest;
use App\Services\Vehicles\FipePlateLookup;
use Illuminate\Http\JsonResponse;

class VehicleLookupController extends Controller
{
    public function __invoke(LookupVehicleRequest $request, FipePlateLookup $lookup): JsonResponse
    {
        $result = $lookup->lookup($request->validated('license_plate'));

        return response()->json($result['body'], $result['status']);
    }
}
