<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TripController extends Controller
{
    public function saveTrip(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => ['nullable', 'string'],
                'origin' => ['required', 'array'],
                'destination' => ['required', 'array'],
                'distance_meters' => ['required', 'numeric', 'min:0'],
                'duration_seconds' => ['required', 'numeric', 'min:0'],
                'mode' => ['required', 'in:car,ev,train,flight,auto'],
                'cost' => ['required', 'array'],
                'passengers' => ['required', 'integer', 'min:1'],
                'polyline' => ['nullable', 'string'],
                'waypoints' => ['nullable', 'array'],
                'saved_preferences' => ['nullable', 'array'],
            ]);

            $trip = Trip::create([
                'user_id' => Auth::id(),
                ...$validated,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Trip saved successfully',
                'data' => $trip,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function listTrips(Request $request)
    {
        try {
            $trips = Trip::where('user_id', Auth::id())
                ->orderBy('created_at', 'desc')
                ->paginate($request->per_page ?? 20);

            return response()->json([
                'status' => 'success',
                'data' => $trips,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function getTrip($id)
    {
        try {
            $trip = Trip::findOrFail($id);

            if ($trip->user_id !== Auth::id()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized',
                ], 403);
            }

            return response()->json([
                'status' => 'success',
                'data' => $trip,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 404);
        }
    }

    public function deleteTrip($id)
    {
        try {
            $trip = Trip::findOrFail($id);

            if ($trip->user_id !== Auth::id()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized',
                ], 403);
            }

            $trip->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Trip deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 404);
        }
    }
}
