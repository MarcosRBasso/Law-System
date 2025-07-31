<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Http\Requests\StoreDocumentRequest;
use App\Http\Requests\UpdateDocumentRequest;
use App\Http\Resources\DocumentResource;
use App\Services\DocumentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class DocumentController extends Controller
{
    public function __construct(
        private DocumentService $documentService
    ) {
        $this->middleware('auth:sanctum');
        $this->authorizeResource(Document::class, 'document');
    }

    /**
     * Display a listing of documents
     */
    public function index(Request $request): JsonResponse
    {
        $documents = $this->documentService->getDocuments($request->all());
        
        return response()->json([
            'data' => DocumentResource::collection($documents->items()),
            'meta' => [
                'current_page' => $documents->currentPage(),
                'last_page' => $documents->lastPage(),
                'per_page' => $documents->perPage(),
                'total' => $documents->total(),
            ]
        ]);
    }

    /**
     * Store a newly created document
     */
    public function store(StoreDocumentRequest $request): JsonResponse
    {
        $document = $this->documentService->createDocument($request->validated());
        
        return response()->json([
            'message' => 'Documento criado com sucesso',
            'data' => new DocumentResource($document)
        ], 201);
    }

    /**
     * Display the specified document
     */
    public function show(Document $document): JsonResponse
    {
        $document->load([
            'lawsuit',
            'client',
            'creator',
            'versions' => fn($q) => $q->latest(),
            'signatures'
        ]);
        
        return response()->json([
            'data' => new DocumentResource($document)
        ]);
    }

    /**
     * Update the specified document
     */
    public function update(UpdateDocumentRequest $request, Document $document): JsonResponse
    {
        $document = $this->documentService->updateDocument($document, $request->validated());
        
        return response()->json([
            'message' => 'Documento atualizado com sucesso',
            'data' => new DocumentResource($document)
        ]);
    }

    /**
     * Remove the specified document
     */
    public function destroy(Document $document): JsonResponse
    {
        $this->documentService->deleteDocument($document);
        
        return response()->json([
            'message' => 'Documento removido com sucesso'
        ]);
    }

    /**
     * Upload document file
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:10240', // 10MB
            'lawsuit_id' => 'nullable|exists:lawsuits,id',
            'client_id' => 'nullable|exists:clients,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string'
        ]);

        $document = $this->documentService->uploadDocument($request->all());
        
        return response()->json([
            'message' => 'Documento enviado com sucesso',
            'data' => new DocumentResource($document)
        ], 201);
    }

    /**
     * Download document
     */
    public function download(Document $document): Response
    {
        $this->authorize('view', $document);
        
        return $this->documentService->downloadDocument($document);
    }

    /**
     * Generate document from template
     */
    public function generateFromTemplate(Request $request): JsonResponse
    {
        $request->validate([
            'template_id' => 'required|exists:document_templates,id',
            'lawsuit_id' => 'nullable|exists:lawsuits,id',
            'client_id' => 'nullable|exists:clients,id',
            'variables' => 'required|array',
            'name' => 'required|string|max:255'
        ]);

        $document = $this->documentService->generateFromTemplate($request->all());
        
        return response()->json([
            'message' => 'Documento gerado com sucesso',
            'data' => new DocumentResource($document)
        ], 201);
    }

    /**
     * Create new version of document
     */
    public function createVersion(Request $request, Document $document): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:10240',
            'changes_description' => 'nullable|string'
        ]);

        $version = $this->documentService->createVersion($document, $request->all());
        
        return response()->json([
            'message' => 'Nova versão criada com sucesso',
            'data' => $version
        ], 201);
    }

    /**
     * Request digital signature
     */
    public function requestSignature(Request $request, Document $document): JsonResponse
    {
        $request->validate([
            'signers' => 'required|array',
            'signers.*.name' => 'required|string',
            'signers.*.email' => 'required|email',
            'signers.*.document' => 'required|string'
        ]);

        $signatures = $this->documentService->requestSignature($document, $request->signers);
        
        return response()->json([
            'message' => 'Solicitação de assinatura enviada com sucesso',
            'data' => $signatures
        ]);
    }

    /**
     * Get document audit trail
     */
    public function auditTrail(Document $document): JsonResponse
    {
        $this->authorize('view', $document);
        
        $auditTrail = $this->documentService->getAuditTrail($document);
        
        return response()->json([
            'data' => $auditTrail
        ]);
    }
}