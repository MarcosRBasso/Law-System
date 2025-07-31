<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Http\Resources\InvoiceResource;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class InvoiceController extends Controller
{
    public function __construct(
        private InvoiceService $invoiceService
    ) {
        $this->middleware('auth:sanctum');
        $this->authorizeResource(Invoice::class, 'invoice');
    }

    /**
     * Display a listing of invoices
     */
    public function index(Request $request): JsonResponse
    {
        $invoices = $this->invoiceService->getInvoices($request->all());
        
        return response()->json([
            'data' => InvoiceResource::collection($invoices->items()),
            'meta' => [
                'current_page' => $invoices->currentPage(),
                'last_page' => $invoices->lastPage(),
                'per_page' => $invoices->perPage(),
                'total' => $invoices->total(),
            ]
        ]);
    }

    /**
     * Store a newly created invoice
     */
    public function store(StoreInvoiceRequest $request): JsonResponse
    {
        $invoice = $this->invoiceService->createInvoice($request->validated());
        
        return response()->json([
            'message' => 'Fatura criada com sucesso',
            'data' => new InvoiceResource($invoice)
        ], 201);
    }

    /**
     * Display the specified invoice
     */
    public function show(Invoice $invoice): JsonResponse
    {
        $invoice->load(['client', 'items.timeEntry', 'payments.paymentMethod']);
        
        return response()->json([
            'data' => new InvoiceResource($invoice)
        ]);
    }

    /**
     * Update the specified invoice
     */
    public function update(UpdateInvoiceRequest $request, Invoice $invoice): JsonResponse
    {
        $invoice = $this->invoiceService->updateInvoice($invoice, $request->validated());
        
        return response()->json([
            'message' => 'Fatura atualizada com sucesso',
            'data' => new InvoiceResource($invoice)
        ]);
    }

    /**
     * Remove the specified invoice
     */
    public function destroy(Invoice $invoice): JsonResponse
    {
        $this->invoiceService->deleteInvoice($invoice);
        
        return response()->json([
            'message' => 'Fatura removida com sucesso'
        ]);
    }

    /**
     * Generate invoice from time entries
     */
    public function generateFromTimeEntries(Request $request): JsonResponse
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'time_entry_ids' => 'required|array',
            'time_entry_ids.*' => 'exists:time_entries,id',
            'due_date' => 'required|date|after:today',
            'notes' => 'nullable|string'
        ]);

        $invoice = $this->invoiceService->generateFromTimeEntries($request->all());
        
        return response()->json([
            'message' => 'Fatura gerada com sucesso',
            'data' => new InvoiceResource($invoice)
        ], 201);
    }

    /**
     * Send invoice to client
     */
    public function send(Invoice $invoice): JsonResponse
    {
        $this->authorize('update', $invoice);
        
        $this->invoiceService->sendInvoice($invoice);
        
        return response()->json([
            'message' => 'Fatura enviada com sucesso'
        ]);
    }

    /**
     * Mark invoice as paid
     */
    public function markAsPaid(Request $request, Invoice $invoice): JsonResponse
    {
        $this->authorize('update', $invoice);
        
        $request->validate([
            'payment_date' => 'nullable|date',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'amount' => 'required|numeric|min:0',
            'reference' => 'nullable|string'
        ]);

        $this->invoiceService->markAsPaid($invoice, $request->all());
        
        return response()->json([
            'message' => 'Fatura marcada como paga'
        ]);
    }

    /**
     * Generate PDF
     */
    public function pdf(Invoice $invoice): Response
    {
        $this->authorize('view', $invoice);
        
        return $this->invoiceService->generatePdf($invoice);
    }

    /**
     * Get overdue invoices
     */
    public function overdue(): JsonResponse
    {
        $invoices = $this->invoiceService->getOverdueInvoices();
        
        return response()->json([
            'data' => InvoiceResource::collection($invoices)
        ]);
    }

    /**
     * Get invoice statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date'
        ]);

        $stats = $this->invoiceService->getStatistics($request->all());
        
        return response()->json([
            'data' => $stats
        ]);
    }

    /**
     * Duplicate invoice
     */
    public function duplicate(Invoice $invoice): JsonResponse
    {
        $this->authorize('create', Invoice::class);
        
        $newInvoice = $this->invoiceService->duplicateInvoice($invoice);
        
        return response()->json([
            'message' => 'Fatura duplicada com sucesso',
            'data' => new InvoiceResource($newInvoice)
        ], 201);
    }
}