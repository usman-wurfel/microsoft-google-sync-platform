<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SyncController;
use App\Http\Controllers\MicrosoftSyncController;
use App\Http\Controllers\GoogleSyncController;
use App\Http\Controllers\RoleController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('sync.dashboard');
});

Route::get('/dashboard', function () {
    return redirect()->route('sync.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Proxy endpoint for external clients (accessible without auth, but OAuth will handle authentication)
Route::get('/ms/sync', [MicrosoftSyncController::class, 'proxy'])->name('ms.sync.proxy');

// Microsoft OAuth callback (public for OAuth flow)
Route::get('/sync/microsoft/callback', [MicrosoftSyncController::class, 'callback'])->name('sync.microsoft.callback');

// Response back URL handler for receiving data from sync
Route::post('/responsebackurl', [MicrosoftSyncController::class, 'callbackHandler'])->name('ms.responsebackurl');
Route::get('/responsebackurl', [MicrosoftSyncController::class, 'callbackHandler'])->name('ms.responsebackurl.get');

// Phishing report API: callable by Outlook add-in without auth when tenant_id + user are sent (app-only)
Route::post('/api/phishing/report', [App\Http\Controllers\PhishingReportController::class, 'report'])->name('api.phishing.report');

// Outlook Add-in Taskpane (public route - loaded inside Outlook)
Route::get('/phishing-addin', function() {
    return view('integrations.phishing-addin');
})->name('phishing.addin');

Route::middleware(['auth', 'prevent.back'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // Sync routes with permissions
    Route::middleware('permission:view-sync-dashboard')->group(function () {
        Route::get('/sync', [SyncController::class, 'dashboard'])->name('sync.dashboard');
        Route::get('/users/{user}', [SyncController::class, 'showUser'])->name('users.show');
    });
    
    Route::middleware('permission:export-data')->group(function () {
        Route::get('/sync/export', [SyncController::class, 'exportJson'])->name('sync.export');
        Route::get('/sync/export/users', [SyncController::class, 'exportUsers'])->name('sync.export.users');
        Route::get('/sync/export/groups', [SyncController::class, 'exportGroups'])->name('sync.export.groups');
    });
    
    // Microsoft sync routes (redirect only, callback is public)
    Route::middleware('permission:sync-microsoft')->group(function () {
        Route::get('/sync/microsoft', [MicrosoftSyncController::class, 'redirect'])->name('sync.microsoft');
    });
    
    // Google sync routes
    Route::middleware('permission:sync-google')->group(function () {
        Route::get('/sync/google', [GoogleSyncController::class, 'redirect'])->name('sync.google');
        Route::get('/sync/google/callback', [GoogleSyncController::class, 'callback'])->name('sync.google.callback');
    });

    // Role management routes (admin only)
    Route::middleware('role:admin')->group(function () {
        Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
        Route::post('/users', [RoleController::class, 'store'])->name('users.store');
        Route::post('/users/{user}/assign-role', [RoleController::class, 'assignRole'])->name('users.assign-role');
        Route::delete('/users/{user}/roles/{role}', [RoleController::class, 'revokeRole'])->name('users.revoke-role');
        Route::delete('/users/{user}', [RoleController::class, 'destroy'])->name('users.destroy');
    });

    // Settings routes (admin only)
    Route::middleware('role:admin')->group(function () {
        Route::get('/settings', [App\Http\Controllers\SettingsController::class, 'index'])->name('settings.index');
        Route::put('/settings', [App\Http\Controllers\SettingsController::class, 'update'])->name('settings.update');
    });
    
    // Integrations routes
    Route::get('/integrations', [App\Http\Controllers\IntegrationsController::class, 'index'])->name('integrations.index');
    Route::get('/integrations/disconnect/{id}', [App\Http\Controllers\IntegrationsController::class, 'disconnect'])->name('integrations.disconnect');
    
    // Graph Mail Delivery routes
    Route::prefix('integrations/graph-mail')->group(function () {
        Route::get('/connect', [App\Http\Controllers\GraphMailController::class, 'connect'])->name('integrations.graph-mail.connect');
        Route::get('/callback', [App\Http\Controllers\GraphMailController::class, 'callback'])->name('integrations.graph-mail.callback');
        Route::get('/test', function() {
            return view('integrations.graph-mail-test');
        })->name('integrations.graph-mail.test');
    });
    
    // Phishing Report routes
    Route::prefix('integrations/phishing')->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\PhishingReportController::class, 'index'])->name('integrations.phishing.dashboard');
        Route::post('/reports/{report}/purge', [App\Http\Controllers\PhishingReportController::class, 'purge'])->name('integrations.phishing.purge');
        Route::get('/connect', [App\Http\Controllers\PhishingReportController::class, 'connect'])->name('integrations.phishing.connect');
        Route::get('/callback', [App\Http\Controllers\PhishingReportController::class, 'callback'])->name('integrations.phishing.callback');
        Route::get('/manifest', [App\Http\Controllers\PhishingReportController::class, 'generateManifest'])->name('integrations.phishing.manifest');
        Route::get('/messages', [App\Http\Controllers\PhishingReportController::class, 'getMessages'])->name('integrations.phishing.messages');
        Route::get('/test', function() {
            return view('integrations.phishing-test');
        })->name('integrations.phishing.test');
    });
    
    // Automated Allowlisting routes
    Route::prefix('integrations/allowlist')->group(function () {
        Route::get('/connect', [App\Http\Controllers\AllowlistController::class, 'connect'])->name('integrations.allowlist.connect');
        Route::get('/callback', [App\Http\Controllers\AllowlistController::class, 'callback'])->name('integrations.allowlist.callback');
        Route::post('/sync', [App\Http\Controllers\AllowlistController::class, 'sync'])->name('integrations.allowlist.sync');
        Route::get('/status', [App\Http\Controllers\AllowlistController::class, 'status'])->name('integrations.allowlist.status');
        Route::get('/test', function() {
            return view('integrations.allowlist-test');
        })->name('integrations.allowlist.test');
    });
    
    // API routes for integrations (requires authentication for session access)
    Route::prefix('api')->group(function () {
        Route::post('/mail/send', [App\Http\Controllers\GraphMailController::class, 'sendEmail'])->name('api.mail.send');
    });
});

require __DIR__.'/auth.php';