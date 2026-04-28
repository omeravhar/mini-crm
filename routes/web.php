<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AuthActivityLogController;
use App\Http\Controllers\AuthSessionController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IntegrationController;
use App\Http\Controllers\IntegrationWebhookController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\LeadStatusController;
use App\Http\Controllers\NotificationController;

Route::withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])->group(function () {
    Route::get('/webhooks/meta/{integration:webhook_key}', [IntegrationWebhookController::class, 'metaVerify'])->name('webhooks.meta.verify');
    Route::post('/webhooks/meta/{integration:webhook_key}', [IntegrationWebhookController::class, 'metaReceive'])->name('webhooks.meta.receive');
    Route::post('/webhooks/google/{integration:webhook_key}', [IntegrationWebhookController::class, 'googleReceive'])->name('webhooks.google.receive');
    Route::post('/webhooks/tiktok/{integration:webhook_key}', [IntegrationWebhookController::class, 'tiktokReceive'])->name('webhooks.tiktok.receive');
    Route::post('/webhooks/generic/{integration:webhook_key}', [IntegrationWebhookController::class, 'genericReceive'])->name('webhooks.generic.receive');
});

Route::get('/', [AuthSessionController::class, 'showLogin']);
Route::get('/login', [AuthSessionController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthSessionController::class, 'login'])->name('login.submit');
Route::post('/logout', [AuthSessionController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/main', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/myLeads', [LeadController::class, 'myLeads'])->name('leads.my');
    Route::get('/leads/{lead}/edit', [LeadController::class, 'edit'])->name('leads.edit');
    Route::put('/leads/{lead}', [LeadController::class, 'update'])->name('leads.update');
    Route::post('/leads/{lead}/quick-update', [LeadController::class, 'quickUpdate'])->name('leads.quick-update');
    Route::delete('/leads/{lead}', [LeadController::class, 'destroy'])->name('leads.destroy');
    Route::post('/leads/{lead}/convert', [LeadController::class, 'convertToCustomer'])->name('leads.convert');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-today', [NotificationController::class, 'markTodayLeadRemindersAsRead'])->name('notifications.readToday');
    Route::get('/notifications/lead-assignment-popups', [NotificationController::class, 'leadAssignmentPopups'])->name('notifications.leadAssignmentPopups');

    Route::get('/allCustomers', [CustomerController::class, 'index'])->name('customers.index');
    Route::post('/allCustomers', [CustomerController::class, 'store'])->name('customers.store');
    Route::put('/allCustomers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
    Route::delete('/allCustomers/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/newLead', [LeadController::class, 'create'])->name('leads.create');
        Route::post('/newLead', [LeadController::class, 'store'])->name('saveNewLead');
        Route::get('/showAllLeads', [LeadController::class, 'index'])->name('leads.index');
        Route::post('/leads/{lead}/assign', [LeadController::class, 'assign'])->name('leads.assign');
        Route::get('/lead-statuses', [LeadStatusController::class, 'index'])->name('lead-statuses.index');
        Route::post('/lead-statuses', [LeadStatusController::class, 'store'])->name('lead-statuses.store');
        Route::put('/lead-statuses/{leadStatus}', [LeadStatusController::class, 'update'])->name('lead-statuses.update');
        Route::delete('/lead-statuses/{leadStatus}', [LeadStatusController::class, 'destroy'])->name('lead-statuses.destroy');
        Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
        Route::get('/auth-activity', [AuthActivityLogController::class, 'index'])->name('auth-activity.index');
        Route::get('/integrations', [IntegrationController::class, 'index'])->name('integrations.index');
        Route::post('/integrations', [IntegrationController::class, 'store'])->name('integrations.store');
        Route::post('/integrations/test', [IntegrationController::class, 'testStorePayload'])->name('integrations.test');
        Route::put('/integrations/{integration}', [IntegrationController::class, 'update'])->name('integrations.update');
        Route::post('/integrations/{integration}/test', [IntegrationController::class, 'testUpdatePayload'])->name('integrations.test.saved');
        Route::delete('/integrations/{integration}', [IntegrationController::class, 'destroy'])->name('integrations.destroy');
        Route::post('/integrations/{integration}/mappings', [IntegrationController::class, 'storeMapping'])->name('integrations.mappings.store');
        Route::put('/integrations/mappings/{mapping}', [IntegrationController::class, 'updateMapping'])->name('integrations.mappings.update');
        Route::delete('/integrations/mappings/{mapping}', [IntegrationController::class, 'destroyMapping'])->name('integrations.mappings.destroy');

        Route::get('/showUsers', [AdminUserController::class, 'index'])->name('users.index');
        Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');
    });
});
