<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use Illuminate\Http\Request;

class TripController extends Controller
{
    /**
     * Get all trips
     */
    public function index()
    {
        $trips = Trip::all();
        return response()->json(['success' => true, 'data' => $trips]);
    }

    /**
     * Create a new trip
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string',
            'start_location' => 'required|string',
            'end_location' => 'required|string',
            'mode' => 'required|in:car,bike,walk,transit',
        ]);

        $trip = Trip::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Trip created successfully',
            'data' => $trip,
        ], 201);
    }

    /**
     * Get a specific trip
     */
    public function show($id)
    {
        $trip = Trip::findOrFail($id);
        return response()->json(['success' => true, 'data' => $trip]);
    }

    /**
     * Update a trip
     */
    public function update(Request $request, $id)
    {
        $trip = Trip::findOrFail($id);
        $trip->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Trip updated successfully',
            'data' => $trip,
        ]);
    }

    /**
     * Delete a trip
     */
    public function destroy($id)
    {
        Trip::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Trip deleted successfully']);
    }
}
