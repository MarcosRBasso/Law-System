<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Models\Payment;
use App\Services\BillingService;
use App\Services\FinancialService;
use App\Notifications\InvoicePaidNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessInvoicePaymentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120;
    public $tries = 3;

    public function __construct(
        private Invoice $invoice,
        private array $paymentData
    ) {
        $this->onQueue('payments');
    }

    /**
     * Execute the job.
     */
    public function handle(BillingService $billingService, FinancialService $financialService): void
    {
        try {
            Log::info("Processing payment for invoice: {$this->invoice->invoice_number}");

            // Process the payment
            $payment = $billingService->processPayment($this->invoice, $this->paymentData);

            // Create financial transaction if account is specified
            if (isset($this->paymentData['account_id'])) {
                $financialService->createTransactionFromPayment($payment);
            }

            // Send notification to client and lawyer
            $this->invoice->client->notify(new InvoicePaidNotification($this->invoice, $payment));
            $this->invoice->creator->notify(new InvoicePaidNotification($this->invoice, $payment));

            Log::info("Payment processed successfully", [
                'invoice_number' => $this->invoice->invoice_number,
                'payment_amount' => $payment->amount,
                'payment_method' => $payment->paymentMethod->name
            ]);

            // If invoice is fully paid, trigger additional actions
            if ($this->invoice->fresh()->status === 'paid') {
                $this->handleFullyPaidInvoice();
            }

        } catch (\Exception $e) {
            Log::error("Failed to process invoice payment", [
                'invoice_number' => $this->invoice->invoice_number,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Handle actions when invoice is fully paid
     */
    private function handleFullyPaidInvoice(): void
    {
        // Update related time entries
        $this->invoice->items()->whereNotNull('time_entry_id')->each(function ($item) {
            $item->timeEntry?->update(['is_billed' => true]);
        });

        // Generate receipt
        GenerateReceiptJob::dispatch($this->invoice);

        // Update client payment history
        UpdateClientPaymentHistoryJob::dispatch($this->invoice->client);

        Log::info("Invoice fully paid - additional actions triggered", [
            'invoice_number' => $this->invoice->invoice_number
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Invoice payment processing failed", [
            'invoice_number' => $this->invoice->invoice_number,
            'error' => $exception->getMessage()
        ]);

        // Notify administrators
        SendSystemAlertJob::dispatch(
            'Payment Processing Failed',
            "Failed to process payment for invoice {$this->invoice->invoice_number}: {$exception->getMessage()}"
        );
    }
}