<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\LawsuitController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\TimeEntryController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\FinancialController;
use App\Http\Controllers\Api\CalendarController;
use App\Http\Controllers\Api\DeadlineController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ReportController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Authentication routes
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
    
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('user', [AuthController::class, 'user']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::put('profile', [AuthController::class, 'updateProfile']);
        Route::post('change-password', [AuthController::class, 'changePassword']);
    });
});

// Protected API routes
Route::middleware('auth:sanctum')->group(function () {
    
    // Dashboard
    Route::get('dashboard', [DashboardController::class, 'index']);
    Route::get('dashboard/widgets/{widget}', [DashboardController::class, 'widget']);
    
    // Clients
    Route::apiResource('clients', ClientController::class);
    Route::prefix('clients')->group(function () {
        Route::post('import', [ClientController::class, 'import']);
        Route::get('export', [ClientController::class, 'export']);
        Route::get('statistics', [ClientController::class, 'statistics']);
        Route::get('search', [ClientController::class, 'search']);
        Route::post('{client}/interactions', [ClientController::class, 'addInteraction']);
        Route::get('{client}/interactions', [ClientController::class, 'getInteractions']);
    });
    
    // Lawsuits
    Route::apiResource('lawsuits', LawsuitController::class);
    Route::prefix('lawsuits')->group(function () {
        Route::get('statistics', [LawsuitController::class, 'statistics']);
        Route::get('overdue', [LawsuitController::class, 'overdue']);
        Route::get('{lawsuit}/movements', [LawsuitController::class, 'movements']);
        Route::post('{lawsuit}/movements', [LawsuitController::class, 'addMovement']);
        Route::post('{lawsuit}/sync-movements', [LawsuitController::class, 'syncMovements']);
        Route::get('{lawsuit}/timeline', [LawsuitController::class, 'timeline']);
    });
    
    // Documents
    Route::apiResource('documents', DocumentController::class);
    Route::prefix('documents')->group(function () {
        Route::post('upload', [DocumentController::class, 'upload']);
        Route::get('{document}/download', [DocumentController::class, 'download']);
        Route::post('generate-from-template', [DocumentController::class, 'generateFromTemplate']);
        Route::post('{document}/versions', [DocumentController::class, 'createVersion']);
        Route::post('{document}/request-signature', [DocumentController::class, 'requestSignature']);
        Route::get('{document}/audit-trail', [DocumentController::class, 'auditTrail']);
    });
    
    // Time Tracking
    Route::apiResource('time-entries', TimeEntryController::class);
    Route::prefix('time-entries')->group(function () {
        Route::post('start', [TimeEntryController::class, 'start']);
        Route::post('{timeEntry}/stop', [TimeEntryController::class, 'stop']);
        Route::get('running', [TimeEntryController::class, 'running']);
        Route::get('statistics', [TimeEntryController::class, 'statistics']);
        Route::get('unbilled', [TimeEntryController::class, 'unbilled']);
        Route::post('mark-as-billed', [TimeEntryController::class, 'markAsBilled']);
    });
    
    // Invoices
    Route::apiResource('invoices', InvoiceController::class);
    Route::prefix('invoices')->group(function () {
        Route::post('generate-from-time-entries', [InvoiceController::class, 'generateFromTimeEntries']);
        Route::post('{invoice}/send', [InvoiceController::class, 'send']);
        Route::post('{invoice}/mark-as-paid', [InvoiceController::class, 'markAsPaid']);
        Route::get('{invoice}/pdf', [InvoiceController::class, 'pdf']);
        Route::get('overdue', [InvoiceController::class, 'overdue']);
        Route::get('statistics', [InvoiceController::class, 'statistics']);
        Route::post('{invoice}/duplicate', [InvoiceController::class, 'duplicate']);
    });
    
    // Financial
    Route::prefix('financial')->group(function () {
        Route::get('dashboard', [FinancialController::class, 'dashboard']);
        Route::get('cash-flow', [FinancialController::class, 'cashFlow']);
        Route::get('profit-loss', [FinancialController::class, 'profitLoss']);
        Route::get('accounts', [FinancialController::class, 'accounts']);
        Route::get('transactions', [FinancialController::class, 'transactions']);
        Route::post('transactions', [FinancialController::class, 'storeTransaction']);
        Route::put('transactions/{transaction}', [FinancialController::class, 'updateTransaction']);
        Route::delete('transactions/{transaction}', [FinancialController::class, 'deleteTransaction']);
        Route::post('reconcile', [FinancialController::class, 'reconcile']);
        Route::post('import-bank-statement', [FinancialController::class, 'importBankStatement']);
        Route::get('reports', [FinancialController::class, 'reports']);
        Route::get('category-analysis', [FinancialController::class, 'categoryAnalysis']);
    });
    
    // Calendar & Events
    Route::prefix('calendar')->group(function () {
        Route::get('events', [CalendarController::class, 'events']);
        Route::post('events', [CalendarController::class, 'store']);
        Route::get('events/{event}', [CalendarController::class, 'show']);
        Route::put('events/{event}', [CalendarController::class, 'update']);
        Route::delete('events/{event}', [CalendarController::class, 'destroy']);
        Route::get('upcoming', [CalendarController::class, 'upcoming']);
        Route::get('today', [CalendarController::class, 'today']);
        Route::get('this-week', [CalendarController::class, 'thisWeek']);
    });
    
    // Deadlines
    Route::apiResource('deadlines', DeadlineController::class);
    Route::prefix('deadlines')->group(function () {
        Route::get('pending', [DeadlineController::class, 'pending']);
        Route::get('overdue', [DeadlineController::class, 'overdue']);
        Route::get('due-today', [DeadlineController::class, 'dueToday']);
        Route::get('due-soon', [DeadlineController::class, 'dueSoon']);
        Route::post('{deadline}/complete', [DeadlineController::class, 'complete']);
    });
    
    // Reports
    Route::prefix('reports')->group(function () {
        Route::get('productivity', [ReportController::class, 'productivity']);
        Route::get('financial-summary', [ReportController::class, 'financialSummary']);
        Route::get('client-activity', [ReportController::class, 'clientActivity']);
        Route::get('lawsuit-statistics', [ReportController::class, 'lawsuitStatistics']);
        Route::get('time-tracking', [ReportController::class, 'timeTracking']);
        Route::get('billing-summary', [ReportController::class, 'billingSummary']);
        Route::post('custom', [ReportController::class, 'custom']);
        Route::get('export/{type}', [ReportController::class, 'export']);
    });
    
    // System Administration
    Route::prefix('admin')->middleware('role:admin')->group(function () {
        Route::get('users', [AdminController::class, 'users']);
        Route::post('users', [AdminController::class, 'createUser']);
        Route::put('users/{user}', [AdminController::class, 'updateUser']);
        Route::delete('users/{user}', [AdminController::class, 'deleteUser']);
        Route::get('system-info', [AdminController::class, 'systemInfo']);
        Route::get('activity-log', [AdminController::class, 'activityLog']);
        Route::post('backup', [AdminController::class, 'backup']);
        Route::get('settings', [AdminController::class, 'settings']);
        Route::put('settings', [AdminController::class, 'updateSettings']);
    });
    
    // Search
    Route::get('search', [SearchController::class, 'global']);
    Route::get('search/clients', [SearchController::class, 'clients']);
    Route::get('search/lawsuits', [SearchController::class, 'lawsuits']);
    Route::get('search/documents', [SearchController::class, 'documents']);
    
    // Notifications
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::post('{notification}/mark-as-read', [NotificationController::class, 'markAsRead']);
        Route::post('mark-all-as-read', [NotificationController::class, 'markAllAsRead']);
        Route::delete('{notification}', [NotificationController::class, 'destroy']);
        Route::get('unread-count', [NotificationController::class, 'unreadCount']);
    });
    
    // File Management
    Route::prefix('files')->group(function () {
        Route::post('upload', [FileController::class, 'upload']);
        Route::get('{file}/download', [FileController::class, 'download']);
        Route::delete('{file}', [FileController::class, 'delete']);
        Route::get('storage-info', [FileController::class, 'storageInfo']);
    });
    
    // Integration endpoints
    Route::prefix('integrations')->group(function () {
        // OAB validation
        Route::post('oab/validate', [IntegrationController::class, 'validateOAB']);
        
        // Court systems
        Route::post('courts/pje/sync', [IntegrationController::class, 'syncPJe']);
        Route::post('courts/eproc/sync', [IntegrationController::class, 'syncEProc']);
        Route::post('courts/saj/sync', [IntegrationController::class, 'syncSAJ']);
        
        // Digital signature
        Route::post('signature/certisign', [IntegrationController::class, 'certisignWebhook']);
        Route::post('signature/serasa', [IntegrationController::class, 'serasaWebhook']);
        
        // SMS/Email
        Route::post('sms/send', [IntegrationController::class, 'sendSMS']);
        Route::post('email/send', [IntegrationController::class, 'sendEmail']);
    });
});

// Public routes (webhooks, etc.)
Route::prefix('webhooks')->group(function () {
    Route::post('signature/certisign', [WebhookController::class, 'certisign']);
    Route::post('signature/serasa', [WebhookController::class, 'serasa']);
    Route::post('payment/callback', [WebhookController::class, 'payment']);
});

// Health check
Route::get('health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toISOString(),
        'version' => config('app.version', '1.0.0')
    ]);
});

// API Documentation
Route::get('docs', function () {
    return view('api-docs');
});