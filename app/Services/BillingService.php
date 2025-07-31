<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\TimeEntry;
use App\Models\Payment;
use App\Models\Transaction;
use App\Models\Account;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BillingService
{
    /**
     * Generate invoice from time entries
     */
    public function generateInvoiceFromTimeEntries(
        Client $client,
        Collection $timeEntries,
        array $invoiceData
    ): Invoice {
        return DB::transaction(function () use ($client, $timeEntries, $invoiceData) {
            // Create invoice
            $invoice = Invoice::create([
                'client_id' => $client->id,
                'invoice_number' => $this->generateInvoiceNumber(),
                'issue_date' => now(),
                'due_date' => $invoiceData['due_date'],
                'subtotal' => 0,
                'tax_amount' => $invoiceData['tax_amount'] ?? 0,
                'discount_amount' => $invoiceData['discount_amount'] ?? 0,
                'total_amount' => 0,
                'status' => 'draft',
                'notes' => $invoiceData['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            $subtotal = 0;

            // Create invoice items from time entries
            foreach ($timeEntries as $timeEntry) {
                $itemTotal = ($timeEntry->duration_minutes / 60) * $timeEntry->hourly_rate;
                
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'time_entry_id' => $timeEntry->id,
                    'description' => $timeEntry->description,
                    'quantity' => round($timeEntry->duration_minutes / 60, 2),
                    'unit_price' => $timeEntry->hourly_rate,
                    'total_amount' => $itemTotal,
                ]);

                $subtotal += $itemTotal;

                // Mark time entry as billed
                $timeEntry->update(['is_billed' => true]);
            }

            // Update invoice totals
            $invoice->update([
                'subtotal' => $subtotal,
                'total_amount' => $subtotal + $invoice->tax_amount - $invoice->discount_amount,
            ]);

            return $invoice->fresh(['items', 'client']);
        });
    }

    /**
     * Generate invoice from custom items
     */
    public function generateCustomInvoice(Client $client, array $items, array $invoiceData): Invoice
    {
        return DB::transaction(function () use ($client, $items, $invoiceData) {
            $invoice = Invoice::create([
                'client_id' => $client->id,
                'invoice_number' => $this->generateInvoiceNumber(),
                'issue_date' => $invoiceData['issue_date'] ?? now(),
                'due_date' => $invoiceData['due_date'],
                'subtotal' => 0,
                'tax_amount' => $invoiceData['tax_amount'] ?? 0,
                'discount_amount' => $invoiceData['discount_amount'] ?? 0,
                'total_amount' => 0,
                'status' => 'draft',
                'notes' => $invoiceData['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            $subtotal = 0;

            foreach ($items as $item) {
                $itemTotal = $item['quantity'] * $item['unit_price'];
                
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_amount' => $itemTotal,
                ]);

                $subtotal += $itemTotal;
            }

            $invoice->update([
                'subtotal' => $subtotal,
                'total_amount' => $subtotal + $invoice->tax_amount - $invoice->discount_amount,
            ]);

            return $invoice->fresh(['items', 'client']);
        });
    }

    /**
     * Process payment for invoice
     */
    public function processPayment(Invoice $invoice, array $paymentData): Payment
    {
        return DB::transaction(function () use ($invoice, $paymentData) {
            $payment = Payment::create([
                'invoice_id' => $invoice->id,
                'payment_method_id' => $paymentData['payment_method_id'],
                'amount' => $paymentData['amount'],
                'payment_date' => $paymentData['payment_date'] ?? now(),
                'reference' => $paymentData['reference'] ?? null,
                'notes' => $paymentData['notes'] ?? null,
            ]);

            // Check if invoice is fully paid
            $totalPaid = $invoice->payments()->sum('amount');
            
            if ($totalPaid >= $invoice->total_amount) {
                $invoice->update([
                    'status' => 'paid',
                    'payment_date' => $payment->payment_date,
                ]);
            }

            // Create financial transaction
            if (isset($paymentData['account_id'])) {
                $this->createFinancialTransaction($invoice, $payment, $paymentData['account_id']);
            }

            return $payment;
        });
    }

    /**
     * Calculate invoice totals with taxes and discounts
     */
    public function calculateInvoiceTotals(Invoice $invoice): array
    {
        $subtotal = $invoice->items()->sum('total_amount');
        $taxAmount = $this->calculateTax($subtotal, $invoice->client);
        $discountAmount = $this->calculateDiscount($subtotal, $invoice->client);
        $total = $subtotal + $taxAmount - $discountAmount;

        return [
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'discount_amount' => $discountAmount,
            'total_amount' => $total,
        ];
    }

    /**
     * Calculate tax based on client type and location
     */
    private function calculateTax(float $subtotal, Client $client): float
    {
        // Basic tax calculation - can be enhanced based on business rules
        $taxRate = match ($client->type) {
            'company' => 0.0, // Companies may have different tax rules
            'individual' => 0.0, // No tax for individual clients by default
            default => 0.0,
        };

        return $subtotal * $taxRate;
    }

    /**
     * Calculate discount based on client relationship
     */
    private function calculateDiscount(float $subtotal, Client $client): float
    {
        // Example discount logic - can be enhanced
        $discountRate = 0.0;

        // VIP clients get 5% discount
        if ($client->tags()->where('name', 'VIP')->exists()) {
            $discountRate = 0.05;
        }

        // Long-term clients (more than 2 years) get 2% discount
        if ($client->created_at->diffInYears(now()) >= 2) {
            $discountRate = max($discountRate, 0.02);
        }

        return $subtotal * $discountRate;
    }

    /**
     * Generate unique invoice number
     */
    private function generateInvoiceNumber(): string
    {
        $year = now()->year;
        $month = now()->format('m');
        
        $lastInvoice = Invoice::whereYear('created_at', $year)
            ->whereMonth('created_at', now()->month)
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastInvoice ? (intval(substr($lastInvoice->invoice_number, -4)) + 1) : 1;

        return sprintf('%d%s%04d', $year, $month, $sequence);
    }

    /**
     * Create financial transaction for payment
     */
    private function createFinancialTransaction(Invoice $invoice, Payment $payment, int $accountId): void
    {
        Transaction::create([
            'account_id' => $accountId,
            'client_id' => $invoice->client_id,
            'invoice_id' => $invoice->id,
            'type' => 'income',
            'category_id' => $this->getRevenueCategory(),
            'description' => "Pagamento fatura #{$invoice->invoice_number}",
            'amount' => $payment->amount,
            'transaction_date' => $payment->payment_date,
            'reference' => $payment->reference,
            'is_reconciled' => false,
            'created_by' => auth()->id(),
        ]);

        // Update account balance
        $account = Account::find($accountId);
        $account->updateBalance();
    }

    /**
     * Get revenue category ID
     */
    private function getRevenueCategory(): ?int
    {
        // Return the ID of the revenue category
        // This should be configurable or created during system setup
        return 1; // Placeholder
    }

    /**
     * Calculate billing statistics for a period
     */
    public function getBillingStatistics(string $startDate, string $endDate): array
    {
        $invoices = Invoice::whereBetween('issue_date', [$startDate, $endDate]);

        $totalInvoiced = $invoices->sum('total_amount');
        $totalPaid = $invoices->where('status', 'paid')->sum('total_amount');
        $totalOverdue = $invoices->where('status', 'overdue')->sum('total_amount');
        $averageInvoiceValue = $invoices->avg('total_amount');

        $unbilledHours = TimeEntry::whereBetween('date', [$startDate, $endDate])
            ->where('is_billable', true)
            ->where('is_billed', false)
            ->sum('duration_minutes') / 60;

        $unbilledAmount = TimeEntry::whereBetween('date', [$startDate, $endDate])
            ->where('is_billable', true)
            ->where('is_billed', false)
            ->sum('total_amount');

        return [
            'total_invoiced' => $totalInvoiced,
            'total_paid' => $totalPaid,
            'total_overdue' => $totalOverdue,
            'average_invoice_value' => $averageInvoiceValue,
            'unbilled_hours' => $unbilledHours,
            'unbilled_amount' => $unbilledAmount,
            'collection_rate' => $totalInvoiced > 0 ? ($totalPaid / $totalInvoiced) * 100 : 0,
        ];
    }

    /**
     * Generate recurring invoices
     */
    public function generateRecurringInvoices(): array
    {
        // This would implement logic for recurring billing
        // Based on client contracts, retainer agreements, etc.
        
        $generated = [];
        
        // Example: Monthly retainer invoices
        // This would be enhanced based on business requirements
        
        return $generated;
    }
}