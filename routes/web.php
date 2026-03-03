<?php

use App\Http\Controllers\InstallController;
use App\Http\Controllers\LicenseController;
use App\Http\Controllers\Auth\AdminLoginController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\InstallationHistoryController;
use App\Http\Controllers\Admin\ToolCatalogController;
use App\Http\Controllers\Api\InstallApiController;
use App\Http\Controllers\Api\LicenseCheckController;
use App\Http\Controllers\ToolAssetController;
use Illuminate\Support\Facades\Route;

// Root: redirect to admin (installer is internal only)
Route::get('/', function () {
    return redirect()->route('admin.login');
})->name('home');

// Admin login (simple password from env)
Route::get('/admin/login', [AdminLoginController::class, 'showLoginForm'])->name('admin.login');
Route::post('/admin/login', [AdminLoginController::class, 'login'])->name('admin.login.post');
Route::post('/admin/logout', [AdminLoginController::class, 'logout'])->name('admin.logout');

// Admin panel (protected)
Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/install', [InstallController::class, 'showForm'])->name('install.form');
    Route::post('/install/verify', [InstallController::class, 'verify'])->name('install.verify');
    Route::post('/install/setup', [InstallController::class, 'setupTool'])->name('install.setup');
    Route::post('/install', [InstallController::class, 'install'])->name('install.run');
    Route::get('/licenses', [LicenseController::class, 'index'])->name('licenses.index');
    Route::get('/licenses/create', [LicenseController::class, 'create'])->name('licenses.create');
    Route::post('/licenses', [LicenseController::class, 'store'])->name('licenses.store');
    Route::get('/licenses/{license}', [LicenseController::class, 'show'])->name('licenses.show');
    Route::match(['put', 'patch'], '/licenses/{license}', [LicenseController::class, 'update'])->name('licenses.update');
    Route::delete('/licenses/{license}', [LicenseController::class, 'destroy'])->name('licenses.destroy');
    Route::delete('/licenses-bulk/destroy', [LicenseController::class, 'bulkDestroy'])->name('licenses.bulk-destroy');
    Route::post('/licenses-bulk/revoke', [LicenseController::class, 'bulkRevoke'])->name('licenses.bulk-revoke');
    Route::get('/installation-history', [InstallationHistoryController::class, 'index'])->name('installation-history.index');
    Route::get('/installation-history/{installationHistory}', [InstallationHistoryController::class, 'show'])->name('installation-history.show');
    Route::get('/tools', [ToolCatalogController::class, 'index'])->name('tools.index');
    Route::get('/tools/{toolSlug}/edit', [ToolCatalogController::class, 'edit'])->name('tools.edit');
    Route::put('/tools/{toolSlug}', [ToolCatalogController::class, 'update'])->name('tools.update');
    Route::get('/tools/{toolSlug}', [ToolCatalogController::class, 'show'])->name('tools.show');
});

// API for BDGS / external (e.g. install after purchase)
Route::prefix('api')->name('api.')->group(function () {
    Route::get('/tools', [InstallApiController::class, 'tools'])->name('tools');
    Route::post('/verify', [InstallApiController::class, 'verify'])->name('verify');
    Route::post('/install', [InstallApiController::class, 'install'])->name('install');
    Route::get('/tool-assets/{toolSlug}/{fileName}', [ToolAssetController::class, 'serve'])->name('tool-assets.serve');
    Route::match(['get', 'post'], '/license/check', [LicenseCheckController::class, 'check'])->name('license.check');
});
