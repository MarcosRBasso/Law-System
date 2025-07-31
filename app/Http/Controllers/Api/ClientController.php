<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Http\Resources\ClientResource;
use App\Services\ClientService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ClientController extends Controller
{
    public function __construct(
        private ClientService $clientService
    ) {
        $this->middleware('auth:sanctum');
        $this->authorizeResource(Client::class, 'client');
    }

    /**
     * Display a listing of clients
     */
    public function index(Request $request): JsonResponse
    {
        $clients = $this->clientService->getClients($request->all());
        
        return response()->json([
            'data' => ClientResource::collection($clients->items()),
            'meta' => [
                'current_page' => $clients->currentPage(),
                'last_page' => $clients->lastPage(),
                'per_page' => $clients->perPage(),
                'total' => $clients->total(),
            ]
        ]);
    }

    /**
     * Store a newly created client
     */
    public function store(StoreClientRequest $request): JsonResponse
    {
        $client = $this->clientService->createClient($request->validated());
        
        return response()->json([
            'message' => 'Cliente criado com sucesso',
            'data' => new ClientResource($client)
        ], 201);
    }

    /**
     * Display the specified client
     */
    public function show(Client $client): JsonResponse
    {
        $client->load([
            'contacts',
            'tags',
            'lawsuits.responsibleLawyer',
            'interactions' => fn($q) => $q->latest()->limit(10)
        ]);
        
        return response()->json([
            'data' => new ClientResource($client)
        ]);
    }

    /**
     * Update the specified client
     */
    public function update(UpdateClientRequest $request, Client $client): JsonResponse
    {
        $client = $this->clientService->updateClient($client, $request->validated());
        
        return response()->json([
            'message' => 'Cliente atualizado com sucesso',
            'data' => new ClientResource($client)
        ]);
    }

    /**
     * Remove the specified client
     */
    public function destroy(Client $client): JsonResponse
    {
        $this->clientService->deleteClient($client);
        
        return response()->json([
            'message' => 'Cliente removido com sucesso'
        ]);
    }

    /**
     * Import clients from CSV
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:2048'
        ]);

        $result = $this->clientService->importFromCsv($request->file('file'));
        
        return response()->json([
            'message' => "Importação concluída. {$result['imported']} clientes importados, {$result['errors']} erros.",
            'data' => $result
        ]);
    }

    /**
     * Export clients to CSV
     */
    public function export(Request $request)
    {
        $filters = $request->all();
        return $this->clientService->exportToCsv($filters);
    }

    /**
     * Get client statistics
     */
    public function statistics(): JsonResponse
    {
        $stats = $this->clientService->getStatistics();
        
        return response()->json([
            'data' => $stats
        ]);
    }

    /**
     * Search clients
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:2'
        ]);

        $clients = $this->clientService->search($request->query);
        
        return response()->json([
            'data' => ClientResource::collection($clients)
        ]);
    }
}