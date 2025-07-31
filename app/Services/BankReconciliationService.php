<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\BankStatement;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;

class BankReconciliationService
{
    /**
     * Import and process bank statement
     */
    public function importBankStatement(Account $account, UploadedFile $file): array
    {
        $filePath = $file->store('bank-statements', 'private');
        
        $bankStatement = BankStatement::create([
            'account_id' => $account->id,
            'file_path' => $filePath,
            'statement_date' => now(),
        ]);

        $transactions = $this->parseStatementFile($file);
        $importedCount = 0;
        $matchedCount = 0;
        $errors = [];

        DB::transaction(function () use ($account, $transactions, &$importedCount, &$matchedCount, &$errors) {
            foreach ($transactions as $transactionData) {
                try {
                    $result = $this->processStatementTransaction($account, $transactionData);
                    
                    if ($result['imported']) {
                        $importedCount++;
                    }
                    
                    if ($result['matched']) {
                        $matchedCount++;
                    }
                } catch (\Exception $e) {
                    $errors[] = [
                        'transaction' => $transactionData,
                        'error' => $e->getMessage()
                    ];
                }
            }
        });

        $bankStatement->markAsProcessed($importedCount);

        return [
            'imported' => $importedCount,
            'matched' => $matchedCount,
            'errors' => count($errors),
            'error_details' => $errors,
        ];
    }

    /**
     * Process individual statement transaction
     */
    private function processStatementTransaction(Account $account, array $transactionData): array
    {
        $imported = false;
        $matched = false;

        // Try to find existing transaction by reference or amount/date
        $existingTransaction = $this->findMatchingTransaction($account, $transactionData);

        if ($existingTransaction) {
            // Mark as reconciled
            $existingTransaction->reconcile();
            $matched = true;
        } else {
            // Create new transaction from statement
            $transaction = Transaction::create([
                'account_id' => $account->id,
                'type' => $transactionData['amount'] > 0 ? 'income' : 'expense',
                'description' => $transactionData['description'],
                'amount' => abs($transactionData['amount']),
                'transaction_date' => $transactionData['date'],
                'reference' => $transactionData['reference'] ?? null,
                'is_reconciled' => true,
                'reconciled_at' => now(),
                'created_by' => auth()->id() ?? 1, // System user
            ]);
            
            $imported = true;
        }

        return [
            'imported' => $imported,
            'matched' => $matched,
        ];
    }

    /**
     * Find matching transaction in the system
     */
    private function findMatchingTransaction(Account $account, array $statementData): ?Transaction
    {
        $query = Transaction::where('account_id', $account->id)
            ->where('is_reconciled', false);

        // First try exact reference match
        if (!empty($statementData['reference'])) {
            $transaction = $query->where('reference', $statementData['reference'])->first();
            if ($transaction) {
                return $transaction;
            }
        }

        // Then try amount and date match (within 3 days)
        $startDate = $statementData['date']->copy()->subDays(3);
        $endDate = $statementData['date']->copy()->addDays(3);
        
        return $query->where('amount', abs($statementData['amount']))
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->first();
    }

    /**
     * Parse statement file based on format
     */
    private function parseStatementFile(UploadedFile $file): array
    {
        $extension = $file->getClientOriginalExtension();
        
        return match (strtolower($extension)) {
            'ofx' => $this->parseOFXFile($file),
            'csv' => $this->parseCSVFile($file),
            'xml' => $this->parseXMLFile($file),
            default => throw new \InvalidArgumentException('Unsupported file format')
        };
    }

    /**
     * Parse OFX (Open Financial Exchange) file
     */
    private function parseOFXFile(UploadedFile $file): array
    {
        $content = file_get_contents($file->getRealPath());
        $transactions = [];

        // Simple OFX parsing - would need more robust implementation
        if (preg_match_all('/<STMTTRN>(.*?)<\/STMTTRN>/s', $content, $matches)) {
            foreach ($matches[1] as $transactionBlock) {
                $transaction = [];
                
                if (preg_match('/<TRNAMT>(.*?)<\/TRNAMT>/', $transactionBlock, $amount)) {
                    $transaction['amount'] = floatval($amount[1]);
                }
                
                if (preg_match('/<DTPOSTED>(.*?)<\/DTPOSTED>/', $transactionBlock, $date)) {
                    $transaction['date'] = \Carbon\Carbon::createFromFormat('Ymd', substr($date[1], 0, 8));
                }
                
                if (preg_match('/<MEMO>(.*?)<\/MEMO>/', $transactionBlock, $memo)) {
                    $transaction['description'] = trim($memo[1]);
                }
                
                if (preg_match('/<FITID>(.*?)<\/FITID>/', $transactionBlock, $fitid)) {
                    $transaction['reference'] = trim($fitid[1]);
                }
                
                if (isset($transaction['amount']) && isset($transaction['date'])) {
                    $transactions[] = $transaction;
                }
            }
        }

        return $transactions;
    }

    /**
     * Parse CSV file
     */
    private function parseCSVFile(UploadedFile $file): array
    {
        $transactions = [];
        $handle = fopen($file->getRealPath(), 'r');
        
        // Skip header row
        fgetcsv($handle);
        
        while (($row = fgetcsv($handle)) !== false) {
            // Assuming CSV format: Date, Description, Amount, Reference
            if (count($row) >= 3) {
                $transactions[] = [
                    'date' => \Carbon\Carbon::createFromFormat('Y-m-d', $row[0]),
                    'description' => $row[1],
                    'amount' => floatval($row[2]),
                    'reference' => $row[3] ?? null,
                ];
            }
        }
        
        fclose($handle);
        return $transactions;
    }

    /**
     * Parse XML file (CAMT format)
     */
    private function parseXMLFile(UploadedFile $file): array
    {
        $xml = simplexml_load_file($file->getRealPath());
        $transactions = [];

        // Basic CAMT.053 parsing - would need more robust implementation
        foreach ($xml->xpath('//Ntry') as $entry) {
            $amount = floatval($entry->Amt);
            $creditDebit = (string)$entry->CdtDbtInd;
            
            if ($creditDebit === 'DBIT') {
                $amount = -$amount;
            }
            
            $transactions[] = [
                'date' => \Carbon\Carbon::createFromFormat('Y-m-d', (string)$entry->ValDt->Dt),
                'description' => (string)$entry->NtryDtls->TxDtls->RmtInf->Ustrd,
                'amount' => $amount,
                'reference' => (string)$entry->NtryDtls->TxDtls->Refs->EndToEndId,
            ];
        }

        return $transactions;
    }

    /**
     * Auto-reconcile transactions based on rules
     */
    public function autoReconcile(Account $account): array
    {
        $reconciled = 0;
        $unreconciled = Transaction::where('account_id', $account->id)
            ->where('is_reconciled', false)
            ->get();

        foreach ($unreconciled as $transaction) {
            if ($this->shouldAutoReconcile($transaction)) {
                $transaction->reconcile();
                $reconciled++;
            }
        }

        return [
            'reconciled' => $reconciled,
            'remaining' => $unreconciled->count() - $reconciled,
        ];
    }

    /**
     * Determine if transaction should be auto-reconciled
     */
    private function shouldAutoReconcile(Transaction $transaction): bool
    {
        // Auto-reconcile if:
        // 1. Transaction is older than 30 days
        // 2. Amount is small (less than R$ 10)
        // 3. Has specific patterns in description
        
        if ($transaction->transaction_date->diffInDays(now()) > 30) {
            return true;
        }
        
        if ($transaction->amount < 10) {
            return true;
        }
        
        $autoReconcilePatterns = [
            'taxa',
            'tarifa',
            'anuidade',
            'mensalidade',
            'juros',
        ];
        
        foreach ($autoReconcilePatterns as $pattern) {
            if (stripos($transaction->description, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Generate reconciliation report
     */
    public function generateReconciliationReport(Account $account, string $period = 'month'): array
    {
        $startDate = match ($period) {
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            'quarter' => now()->startOfQuarter(),
            'year' => now()->startOfYear(),
            default => now()->startOfMonth(),
        };

        $endDate = now();

        $transactions = Transaction::where('account_id', $account->id)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->get();

        $reconciled = $transactions->where('is_reconciled', true);
        $unreconciled = $transactions->where('is_reconciled', false);

        return [
            'period' => $period,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'total_transactions' => $transactions->count(),
            'reconciled_count' => $reconciled->count(),
            'unreconciled_count' => $unreconciled->count(),
            'reconciled_amount' => $reconciled->sum('amount'),
            'unreconciled_amount' => $unreconciled->sum('amount'),
            'reconciliation_rate' => $transactions->count() > 0 ? 
                ($reconciled->count() / $transactions->count()) * 100 : 0,
            'unreconciled_transactions' => $unreconciled->values(),
        ];
    }

    /**
     * Mark transactions as reconciled manually
     */
    public function manualReconcile(array $transactionIds): int
    {
        $reconciled = 0;
        
        foreach ($transactionIds as $id) {
            $transaction = Transaction::find($id);
            if ($transaction && !$transaction->is_reconciled) {
                $transaction->reconcile();
                $reconciled++;
            }
        }
        
        return $reconciled;
    }

    /**
     * Suggest potential matches for unreconciled transactions
     */
    public function suggestMatches(Account $account): array
    {
        $unreconciled = Transaction::where('account_id', $account->id)
            ->where('is_reconciled', false)
            ->get();

        $suggestions = [];

        foreach ($unreconciled as $transaction) {
            $potentialMatches = $this->findPotentialMatches($transaction);
            
            if ($potentialMatches->isNotEmpty()) {
                $suggestions[] = [
                    'transaction' => $transaction,
                    'potential_matches' => $potentialMatches,
                    'confidence' => $this->calculateMatchConfidence($transaction, $potentialMatches->first()),
                ];
            }
        }

        return $suggestions;
    }

    /**
     * Find potential matches for a transaction
     */
    private function findPotentialMatches(Transaction $transaction): Collection
    {
        return Transaction::where('account_id', $transaction->account_id)
            ->where('id', '!=', $transaction->id)
            ->where('is_reconciled', false)
            ->where(function ($query) use ($transaction) {
                $query->where('amount', $transaction->amount)
                      ->orWhere('reference', $transaction->reference);
            })
            ->get();
    }

    /**
     * Calculate match confidence percentage
     */
    private function calculateMatchConfidence(Transaction $transaction1, Transaction $transaction2): int
    {
        $confidence = 0;

        // Exact amount match
        if ($transaction1->amount == $transaction2->amount) {
            $confidence += 40;
        }

        // Reference match
        if ($transaction1->reference && $transaction1->reference == $transaction2->reference) {
            $confidence += 30;
        }

        // Date proximity (within 7 days)
        $daysDiff = abs($transaction1->transaction_date->diffInDays($transaction2->transaction_date));
        if ($daysDiff <= 7) {
            $confidence += max(0, 20 - ($daysDiff * 2));
        }

        // Description similarity
        $similarity = 0;
        similar_text(
            strtolower($transaction1->description),
            strtolower($transaction2->description),
            $similarity
        );
        $confidence += intval($similarity * 0.1);

        return min(100, $confidence);
    }
}