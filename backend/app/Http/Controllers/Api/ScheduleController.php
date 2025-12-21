<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    /**
     * Get all schedules
     */
    public function index()
    {
        $schedules = Schedule::orderBy('date', 'asc')
            ->orderBy('time', 'asc')
            ->paginate(10)
            ->through(function ($schedule) {
                return [
                    'id' => $schedule->id,
                    'title' => $schedule->title,
                    'date' => $schedule->date->format('d/m/Y'),
                    'time' => $schedule->time,
                    'description' => $schedule->description,
                ];
            });

        return response()->json([
            'data' => $schedules->items(),
            'total' => $schedules->total(),
            'per_page' => $schedules->perPage(),
            'current_page' => $schedules->currentPage(),
        ], 200);
    }

    /**
     * Get today's schedules
     */
    public function today()
    {
        $today = now()->format('Y-m-d');
        
        $schedules = Schedule::whereDate('date', $today)
            ->orderBy('time', 'asc')
            ->get()
            ->map(function ($schedule) {
                return [
                    'id' => $schedule->id,
                    'title' => $schedule->title,
                    'date' => $schedule->date->format('d/m/Y'),
                    'time' => $schedule->time,
                    'description' => $schedule->description,
                ];
            });

        return response()->json([
            'data' => $schedules,
            'count' => $schedules->count(),
        ], 200);
    }

    /**
     * Get upcoming schedules (next 7 days)
     */
    public function upcoming()
    {
        $today = now();
        $endDate = $today->copy()->addDays(7);
        
        $schedules = Schedule::whereBetween('date', [$today, $endDate])
            ->orderBy('date', 'asc')
            ->orderBy('time', 'asc')
            ->get()
            ->map(function ($schedule) {
                return [
                    'id' => $schedule->id,
                    'title' => $schedule->title,
                    'date' => $schedule->date->format('d/m/Y'),
                    'time' => $schedule->time,
                    'description' => $schedule->description,
                ];
            });

        return response()->json([
            'data' => $schedules,
            'count' => $schedules->count(),
        ], 200);
    }

    /**
     * Get schedule by ID
     */
    public function show($id)
    {
        $schedule = Schedule::findOrFail($id);

        return response()->json([
            'id' => $schedule->id,
            'title' => $schedule->title,
            'date' => $schedule->date->format('d/m/Y'),
            'time' => $schedule->time,
            'description' => $schedule->description,
        ], 200);
    }

    /**
     * Create new schedule (Admin only)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'date' => 'required|date',
            'time' => 'required|date_format:H:i',
            'description' => 'nullable|string',
        ]);

        $schedule = Schedule::create($validated);

        return response()->json([
            'message' => 'Schedule created successfully',
            'data' => [
                'id' => $schedule->id,
                'title' => $schedule->title,
                'date' => $schedule->date->format('d/m/Y'),
                'time' => $schedule->time,
                'description' => $schedule->description,
            ]
        ], 201);
    }

    /**
     * Update schedule (Admin only)
     */
    public function update(Request $request, $id)
    {
        $schedule = Schedule::findOrFail($id);
        
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'date' => 'sometimes|required|date',
            'time' => 'sometimes|required|date_format:H:i',
            'description' => 'nullable|string',
        ]);

        $schedule->update($validated);

        return response()->json([
            'message' => 'Schedule updated successfully',
            'data' => [
                'id' => $schedule->id,
                'title' => $schedule->title,
                'date' => $schedule->date->format('d/m/Y'),
                'time' => $schedule->time,
                'description' => $schedule->description,
            ]
        ], 200);
    }

    /**
     * Delete schedule (Admin only)
     */
    public function destroy($id)
    {
        $schedule = Schedule::findOrFail($id);
        $schedule->delete();

        return response()->json([
            'message' => 'Schedule deleted successfully',
        ], 200);
    }
}
