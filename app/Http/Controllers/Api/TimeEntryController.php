<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TimeEntry;
use App\Http\Requests\StoreTimeEntryRequest;
use App\Http\Requests\UpdateTimeEntryRequest;
use App\Http\Resources\TimeEntryResource;
use App\Services\TimeTrackingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TimeEntryController extends Controller
{
    public function __construct(
        private TimeTrackingService $timeTrackingService
    ) {
        $this->middleware('auth:sanctum');
        $this->authorizeResource(TimeEntry::class, 'timeEntry');
    }

    /**
     * Display a listing of time entries
     */
    public function index(Request $request): JsonResponse
    {
        $timeEntries = $this->timeTrackingService->getTimeEntries($request->all());
        
        return response()->json([
            'data' => TimeEntryResource::collection($timeEntries->items()),
            'meta' => [
                'current_page' => $timeEntries->currentPage(),
                'last_page' => $timeEntries->lastPage(),
                'per_page' => $timeEntries->perPage(),
                'total' => $timeEntries->total(),
            ]
        ]);
    }

    /**
     * Store a newly created time entry
     */
    public function store(StoreTimeEntryRequest $request): JsonResponse
    {
        $timeEntry = $this->timeTrackingService->createTimeEntry($request->validated());
        
        return response()->json([
            'message' => 'Entrada de tempo criada com sucesso',
            'data' => new TimeEntryResource($timeEntry)
        ], 201);
    }

    /**
     * Display the specified time entry
     */
    public function show(TimeEntry $timeEntry): JsonResponse
    {
        $timeEntry->load(['user', 'lawsuit', 'client']);
        
        return response()->json([
            'data' => new TimeEntryResource($timeEntry)
        ]);
    }

    /**
     * Update the specified time entry
     */
    public function update(UpdateTimeEntryRequest $request, TimeEntry $timeEntry): JsonResponse
    {
        $timeEntry = $this->timeTrackingService->updateTimeEntry($timeEntry, $request->validated());
        
        return response()->json([
            'message' => 'Entrada de tempo atualizada com sucesso',
            'data' => new TimeEntryResource($timeEntry)
        ]);
    }

    /**
     * Remove the specified time entry
     */
    public function destroy(TimeEntry $timeEntry): JsonResponse
    {
        $this->timeTrackingService->deleteTimeEntry($timeEntry);
        
        return response()->json([
            'message' => 'Entrada de tempo removida com sucesso'
        ]);
    }

    /**
     * Start time tracking
     */
    public function start(Request $request): JsonResponse
    {
        $request->validate([
            'description' => 'required|string',
            'lawsuit_id' => 'nullable|exists:lawsuits,id',
            'client_id' => 'nullable|exists:clients,id',
            'hourly_rate' => 'required|numeric|min:0'
        ]);

        $timeEntry = $this->timeTrackingService->startTimer($request->all());
        
        return response()->json([
            'message' => 'Timer iniciado com sucesso',
            'data' => new TimeEntryResource($timeEntry)
        ], 201);
    }

    /**
     * Stop time tracking
     */
    public function stop(TimeEntry $timeEntry): JsonResponse
    {
        $this->authorize('update', $timeEntry);
        
        $timeEntry = $this->timeTrackingService->stopTimer($timeEntry);
        
        return response()->json([
            'message' => 'Timer parado com sucesso',
            'data' => new TimeEntryResource($timeEntry)
        ]);
    }

    /**
     * Get running timers
     */
    public function running(): JsonResponse
    {
        $runningTimers = $this->timeTrackingService->getRunningTimers();
        
        return response()->json([
            'data' => TimeEntryResource::collection($runningTimers)
        ]);
    }

    /**
     * Get time tracking statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'user_id' => 'nullable|exists:users,id'
        ]);

        $stats = $this->timeTrackingService->getStatistics($request->all());
        
        return response()->json([
            'data' => $stats
        ]);
    }

    /**
     * Get unbilled time entries
     */
    public function unbilled(Request $request): JsonResponse
    {
        $request->validate([
            'client_id' => 'nullable|exists:clients,id',
            'user_id' => 'nullable|exists:users,id'
        ]);

        $timeEntries = $this->timeTrackingService->getUnbilledEntries($request->all());
        
        return response()->json([
            'data' => TimeEntryResource::collection($timeEntries)
        ]);
    }

    /**
     * Mark time entries as billed
     */
    public function markAsBilled(Request $request): JsonResponse
    {
        $request->validate([
            'time_entry_ids' => 'required|array',
            'time_entry_ids.*' => 'exists:time_entries,id'
        ]);

        $this->timeTrackingService->markAsBilled($request->time_entry_ids);
        
        return response()->json([
            'message' => 'Entradas de tempo marcadas como faturadas'
        ]);
    }
}