<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lawsuit;
use App\Http\Requests\StoreLawsuitRequest;
use App\Http\Requests\UpdateLawsuitRequest;
use App\Http\Resources\LawsuitResource;
use App\Services\LawsuitService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class LawsuitController extends Controller
{
    public function __construct(
        private LawsuitService $lawsuitService
    ) {
        $this->middleware('auth:sanctum');
        $this->authorizeResource(Lawsuit::class, 'lawsuit');
    }

    /**
     * Display a listing of lawsuits
     */
    public function index(Request $request): JsonResponse
    {
        $lawsuits = $this->lawsuitService->getLawsuits($request->all());
        
        return response()->json([
            'data' => LawsuitResource::collection($lawsuits->items()),
            'meta' => [
                'current_page' => $lawsuits->currentPage(),
                'last_page' => $lawsuits->lastPage(),
                'per_page' => $lawsuits->perPage(),
                'total' => $lawsuits->total(),
            ]
        ]);
    }

    /**
     * Store a newly created lawsuit
     */
    public function store(StoreLawsuitRequest $request): JsonResponse
    {
        $lawsuit = $this->lawsuitService->createLawsuit($request->validated());
        
        return response()->json([
            'message' => 'Processo criado com sucesso',
            'data' => new LawsuitResource($lawsuit)
        ], 201);
    }

    /**
     * Display the specified lawsuit
     */
    public function show(Lawsuit $lawsuit): JsonResponse
    {
        $lawsuit->load([
            'client',
            'responsibleLawyer',
            'court',
            'parties.client',
            'movements' => fn($q) => $q->latest()->limit(20),
            'documents' => fn($q) => $q->latest()->limit(10),
            'deadlines' => fn($q) => $q->pending()->orderBy('due_date')
        ]);
        
        return response()->json([
            'data' => new LawsuitResource($lawsuit)
        ]);
    }

    /**
     * Update the specified lawsuit
     */
    public function update(UpdateLawsuitRequest $request, Lawsuit $lawsuit): JsonResponse
    {
        $lawsuit = $this->lawsuitService->updateLawsuit($lawsuit, $request->validated());
        
        return response()->json([
            'message' => 'Processo atualizado com sucesso',
            'data' => new LawsuitResource($lawsuit)
        ]);
    }

    /**
     * Remove the specified lawsuit
     */
    public function destroy(Lawsuit $lawsuit): JsonResponse
    {
        $this->lawsuitService->deleteLawsuit($lawsuit);
        
        return response()->json([
            'message' => 'Processo removido com sucesso'
        ]);
    }

    /**
     * Get lawsuit movements
     */
    public function movements(Lawsuit $lawsuit): JsonResponse
    {
        $movements = $lawsuit->movements()
            ->with('lawsuit')
            ->orderBy('movement_date', 'desc')
            ->paginate(20);
        
        return response()->json([
            'data' => $movements->items(),
            'meta' => [
                'current_page' => $movements->currentPage(),
                'last_page' => $movements->lastPage(),
                'per_page' => $movements->perPage(),
                'total' => $movements->total(),
            ]
        ]);
    }

    /**
     * Add manual movement
     */
    public function addMovement(Request $request, Lawsuit $lawsuit): JsonResponse
    {
        $request->validate([
            'movement_date' => 'required|date',
            'description' => 'required|string',
            'type' => 'nullable|string'
        ]);

        $movement = $this->lawsuitService->addMovement($lawsuit, $request->all());
        
        return response()->json([
            'message' => 'Movimentação adicionada com sucesso',
            'data' => $movement
        ], 201);
    }

    /**
     * Sync movements from court systems
     */
    public function syncMovements(Lawsuit $lawsuit): JsonResponse
    {
        $result = $this->lawsuitService->syncMovements($lawsuit);
        
        return response()->json([
            'message' => "Sincronização concluída. {$result['new_movements']} novas movimentações encontradas.",
            'data' => $result
        ]);
    }

    /**
     * Get lawsuit statistics
     */
    public function statistics(): JsonResponse
    {
        $stats = $this->lawsuitService->getStatistics();
        
        return response()->json([
            'data' => $stats
        ]);
    }

    /**
     * Get overdue lawsuits
     */
    public function overdue(): JsonResponse
    {
        $lawsuits = $this->lawsuitService->getOverdueLawsuits();
        
        return response()->json([
            'data' => LawsuitResource::collection($lawsuits)
        ]);
    }
}