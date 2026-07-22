<?php

use App\Http\Controllers\Auth\AccountActivationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\ContributionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\MeetingRequestController;
use App\Http\Controllers\OwnerController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\TemplateController;
use Illuminate\Support\Facades\Route;

use App\Models\Property;

Route::get('/', function () {
    $reglement = Property::whereNotNull('reglement_fichier')->first();
    return view('welcome', ['reglement' => $reglement]);
});

Route::get('/templates/modele-premiere-assemblee.docx', [TemplateController::class, 'download'])->name('templates.modele-premiere-assemblee');

Route::middleware(['auth'])->group(function () {
    Route::get('/messages', [\App\Http\Controllers\MessageController::class, 'index'])->name('messages.index');
    Route::post('/messages', [\App\Http\Controllers\MessageController::class, 'store'])->name('messages.store');
    Route::get('/messages/{owner}', [\App\Http\Controllers\MessageController::class, 'show'])->name('messages.show');
    Route::get('/messages/{owner}/poll', [\App\Http\Controllers\MessageController::class, 'poll'])->name('messages.poll');
    Route::get('/messages/{owner}/older', [\App\Http\Controllers\MessageController::class, 'olderMessages'])->name('messages.older');
    Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/mark-read', [\App\Http\Controllers\NotificationController::class, 'markRead'])->name('notifications.mark-read');
    Route::get('/my-contribution', [ContributionController::class, 'ownerContribution'])->name('contributions.owner');
    Route::get('/tenants', [\App\Http\Controllers\TenantController::class, 'index'])->name('tenants.index');
    Route::post('/tenants', [\App\Http\Controllers\TenantController::class, 'store'])->name('tenants.store');
    Route::put('/tenants/{tenant}', [\App\Http\Controllers\TenantController::class, 'update'])->name('tenants.update');
    Route::patch('/tenants/{tenant}', [\App\Http\Controllers\TenantController::class, 'update']);
});

Route::middleware(['auth'])->group(function () {
    Route::get('/complaints', [\App\Http\Controllers\ComplaintController::class, 'index'])->name('complaints.index');
    Route::post('/complaints', [\App\Http\Controllers\ComplaintController::class, 'store'])->name('complaints.store');
    Route::post('/complaints/{complaint}/status', [\App\Http\Controllers\ComplaintController::class, 'updateStatus'])->name('complaints.status');
    Route::post('/complaints/{complaint}/validate', [\App\Http\Controllers\ComplaintController::class, 'validateByOwner'])->name('complaints.validate');
    Route::post('/complaints/{complaint}/upload', [\App\Http\Controllers\ComplaintController::class, 'uploadAttachment'])->name('complaints.upload');
    Route::get('/complaints/{complaint}/attachment', [\App\Http\Controllers\ComplaintController::class, 'downloadAttachment'])->name('complaints.attachment');
    Route::get('/meetings', [\App\Http\Controllers\MeetingController::class, 'index'])->name('meetings.index');
    Route::post('/meetings', [\App\Http\Controllers\MeetingController::class, 'store'])->name('meetings.store');
    Route::get('/meetings/{meeting}', [\App\Http\Controllers\MeetingController::class, 'show'])->name('meetings.show');
    Route::put('/meetings/{meeting}', [\App\Http\Controllers\MeetingController::class, 'update'])->name('meetings.update');
    Route::post('/meetings/{meeting}/cancel', [\App\Http\Controllers\MeetingController::class, 'cancel'])->name('meetings.cancel');

    Route::post('/votes', [\App\Http\Controllers\VoteController::class, 'store'])->name('votes.store');
    Route::post('/votes/{vote}/close', [\App\Http\Controllers\VoteController::class, 'close'])->name('votes.close');
    Route::get('/votes/{vote}/results', [\App\Http\Controllers\VoteController::class, 'results'])->name('votes.results');

    Route::post('/votes/{vote}/participate', [\App\Http\Controllers\VoteParticipationController::class, 'store'])->name('votes.participate');
    Route::get('/meeting-requests', [\App\Http\Controllers\MeetingRequestController::class, 'index'])->name('meeting-requests.index');
    Route::post('/meeting-requests', [MeetingRequestController::class, 'store'])->name('meeting-requests.store');
    Route::post('/meeting-requests/{meetingRequest}/vote', [MeetingRequestController::class, 'vote'])->name('meeting-requests.vote');
    Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::post('/contributions/{contribution}/invoices', [\App\Http\Controllers\InvoiceController::class, 'createFromContribution']);
    Route::patch('/invoices/{invoice}/pay', [\App\Http\Controllers\InvoiceController::class, 'markAsPaid']);
    Route::get('/owners/{owner}/invoices', [\App\Http\Controllers\InvoiceController::class, 'forOwner']);
});

Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
Route::post('/register', [RegisteredUserController::class, 'store'])->name('register.store');

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::get('/activation-pending/{email}', [LoginController::class, 'pending'])->name('activation.pending');
Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('auth')->name('dashboard');

Route::get('/activate/resend', [AccountActivationController::class, 'showResendForm'])->name('activate.resend.form');
Route::post('/activate/resend', [AccountActivationController::class, 'resend'])->middleware('throttle:3,10')->name('activate.resend');
Route::get('/activate/{token}', [AccountActivationController::class, 'show'])->name('activate.show');
Route::post('/activate/{token}', [AccountActivationController::class, 'store'])->name('activate.store');

Route::middleware(['auth', 'role:syndic'])->group(function () {
    Route::get('/contributions', [ContributionController::class, 'index'])->name('contributions.index');
    Route::post('/contributions/calculate', [ContributionController::class, 'calculate'])->name('contributions.calculate');
    Route::post('/contributions/{contribution}/toggle-paid', [ContributionController::class, 'togglePaid'])->name('contributions.toggle-paid');
    Route::post('/contributions/surplus', [ContributionController::class, 'addSurplus'])->name('contributions.surplus');
    Route::get('/budgets', [\App\Http\Controllers\BudgetController::class, 'index'])->name('budgets.index');
    Route::post('/owners/{owner}/reset-password', [\App\Http\Controllers\OwnerController::class, 'resetPassword'])->name('owners.reset-password');
    Route::get('/budgets/create', [\App\Http\Controllers\BudgetController::class, 'create'])->name('budgets.create');
    Route::post('/budgets', [\App\Http\Controllers\BudgetController::class, 'store'])->name('budgets.store');
    Route::post('/budgets/{budget}/validate', [\App\Http\Controllers\BudgetController::class, 'markAsValid'])->name('budgets.validate');
        Route::post('/expenses', [\App\Http\Controllers\ExpenseController::class, 'store'])->name('expenses.store');
        Route::post('/expenses/{expense}/upload', [\App\Http\Controllers\ExpenseController::class, 'uploadReceipt'])->name('expenses.upload');
        Route::post('/expenses/{expense}/toggle-paid', [\App\Http\Controllers\ExpenseController::class, 'togglePaid'])->name('expenses.toggle-paid');
        Route::post('/expenses/{expense}/receipt', [\App\Http\Controllers\ExpenseController::class, 'markReceiptProvided'])->name('expenses.mark-receipt');
            Route::post('/invitations', [InvitationController::class, 'store'])->name('invitations.store');
        // complaints routes are accessible to authenticated users (owners and syndics)
        Route::get('/owners', [OwnerController::class, 'index'])->name('owners.index');
        Route::post('/owners', [OwnerController::class, 'store'])->name('owners.store');
        Route::post('/invoices', [\App\Http\Controllers\InvoiceController::class, 'store'])->name('invoices.store');
        Route::post('/invoices/{invoice}/pay', [\App\Http\Controllers\InvoiceController::class, 'markAsPaid'])->name('invoices.pay');
            Route::delete('/tenants/{tenant}', [\App\Http\Controllers\TenantController::class, 'destroy'])->name('tenants.destroy');

    // Import routes for syndic
    Route::get('/import', [\App\Http\Controllers\ImportController::class, 'showUploadForm'])->name('import.upload');
    Route::post('/import/preview', [\App\Http\Controllers\ImportController::class, 'preview'])->name('import.preview');
    Route::post('/import/confirm', [\App\Http\Controllers\ImportController::class, 'confirm'])->name('import.confirm');

    // Reglement upload for syndic
    Route::get('/properties/{property}/reglement/upload', [\App\Http\Controllers\PropertyController::class, 'showUploadReglementForm'])->name('properties.reglement.form');
    Route::post('/properties/{property}/reglement', [\App\Http\Controllers\PropertyController::class, 'uploadReglement'])->name('properties.reglement.upload');

    Route::get('/history', [\App\Http\Controllers\HistoryController::class, 'index'])->name('history.index');
    
        // Meeting report template and upload
        Route::get('/meetings/{meeting}/report/template', [\App\Http\Controllers\MeetingController::class, 'downloadReportTemplate'])->name('meetings.report.template');
        Route::post('/meetings/{meeting}/report', [\App\Http\Controllers\MeetingController::class, 'uploadReport'])->name('meetings.report.upload');
        Route::get('/meetings/{meeting}/report', [\App\Http\Controllers\MeetingController::class, 'downloadReport'])->name('meetings.report.download');
    });

Route::middleware(['auth'])->group(function () {
    Route::get('/expenses', [\App\Http\Controllers\ExpenseController::class, 'index'])->name('expenses.index');
    Route::get('/expenses/{expense}/facture', [\App\Http\Controllers\ExpenseController::class, 'downloadFacture'])->name('expenses.download-facture');

    // Owner profile routes
    Route::get('/profile', [\App\Http\Controllers\ProfileController::class, 'show'])->name('profile.show');
    Route::patch('/profile', [\App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
});

// Public reglement download
Route::get('/properties/{property}/reglement', [\App\Http\Controllers\PropertyController::class, 'downloadReglement'])->name('properties.reglement.download');