<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Account;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Requests\UpdateTransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Http\Resources\AccountResource;
use App\Services\FinancialService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FinancialController extends Controller
{
    public function __construct(
        private FinancialService $financialService
    ) {
        $this->middleware('auth:sanctum');
    }

    /**
     * Get financial dashboard data
     */
    public function dashboard(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'nullable|in:week,month,quarter,year',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date'
        ]);

        $dashboard = $this->financialService->getDashboardData($request->all());
        
        return response()->json([
            'data' => $dashboard
        ]);
    }

    /**
     * Get cash flow data
     */
    public function cashFlow(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'nullable|in:daily,weekly,monthly',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date'
        ]);

        $cashFlow = $this->financialService->getCashFlow($request->all());
        
        return response()->json([
            'data' => $cashFlow
        ]);
    }

    /**
     * Get profit and loss report
     */
    public function profitLoss(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date'
        ]);

        $report = $this->financialService->getProfitLossReport(
            $request->start_date,
            $request->end_date
        );
        
        return response()->json([
            'data' => $report
        ]);
    }

    /**
     * Get accounts list
     */
    public function accounts(): JsonResponse
    {
        $accounts = Account::active()->with('transactions')->get();
        
        return response()->json([
            'data' => AccountResource::collection($accounts)
        ]);
    }

    /**
     * Get transactions list
     */
    public function transactions(Request $request): JsonResponse
    {
        $transactions = $this->financialService->getTransactions($request->all());
        
        return response()->json([
            'data' => TransactionResource::collection($transactions->items()),
            'meta' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ]
        ]);
    }

    /**
     * Store a new transaction
     */
    public function storeTransaction(StoreTransactionRequest $request): JsonResponse
    {
        $transaction = $this->financialService->createTransaction($request->validated());
        
        return response()->json([
            'message' => 'Transação criada com sucesso',
            'data' => new TransactionResource($transaction)
        ], 201);
    }

    /**
     * Update transaction
     */
    public function updateTransaction(UpdateTransactionRequest $request, Transaction $transaction): JsonResponse
    {
        $this->authorize('update', $transaction);
        
        $transaction = $this->financialService->updateTransaction($transaction, $request->validated());
        
        return response()->json([
            'message' => 'Transação atualizada com sucesso',
            'data' => new TransactionResource($transaction)
        ]);
    }

    /**
     * Delete transaction
     */
    public function deleteTransaction(Transaction $transaction): JsonResponse
    {
        $this->authorize('delete', $transaction);
        
        $this->financialService->deleteTransaction($transaction);
        
        return response()->json([
            'message' => 'Transação removida com sucesso'
        ]);
    }

    /**
     * Reconcile transactions
     */
    public function reconcile(Request $request): JsonResponse
    {
        $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'transaction_ids' => 'required|array',
            'transaction_ids.*' => 'exists:transactions,id'
        ]);

        $result = $this->financialService->reconcileTransactions(
            $request->account_id,
            $request->transaction_ids
        );
        
        return response()->json([
            'message' => "Conciliação concluída. {$result['reconciled']} transações conciliadas.",
            'data' => $result
        ]);
    }

    /**
     * Import bank statement
     */
    public function importBankStatement(Request $request): JsonResponse
    {
        $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'file' => 'required|file|mimes:ofx,xml,csv|max:5120'
        ]);

        $result = $this->financialService->importBankStatement(
            $request->account_id,
            $request->file('file')
        );
        
        return response()->json([
            'message' => "Importação concluída. {$result['imported']} transações importadas.",
            'data' => $result
        ]);
    }

    /**
     * Get financial reports
     */
    public function reports(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:balance_sheet,cash_flow,profit_loss,accounts_receivable',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'format' => 'nullable|in:json,pdf,excel'
        ]);

        $report = $this->financialService->generateReport($request->all());
        
        if ($request->format === 'pdf') {
            return $report; // Returns PDF response
        }
        
        if ($request->format === 'excel') {
            return $report; // Returns Excel download
        }
        
        return response()->json([
            'data' => $report
        ]);
    }

    /**
     * Get category spending analysis
     */
    public function categoryAnalysis(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'type' => 'nullable|in:income,expense'
        ]);

        $analysis = $this->financialService->getCategoryAnalysis($request->all());
        
        return response()->json([
            'data' => $analysis
        ]);
    }
}