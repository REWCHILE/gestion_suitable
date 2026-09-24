<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\MlBrainController;
use App\Http\Controllers\WooCommerceController;
use App\Http\Controllers\SettingController;

// Dashboard
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

// Clients Pipeline & Table
Route::get('/clients', [ClientController::class, 'index'])->name('clients.index');
Route::post('/clients', [ClientController::class, 'store'])->name('clients.store');
Route::post('/clients/{client}/status', [ClientController::class, 'updateStatus'])->name('clients.update_status');
Route::delete('/clients/{client}', [ClientController::class, 'destroy'])->name('clients.destroy');

// Groups & Segments
Route::get('/groups', [GroupController::class, 'index'])->name('groups.index');
Route::post('/groups', [GroupController::class, 'store'])->name('groups.store');
Route::delete('/groups/{group}', [GroupController::class, 'destroy'])->name('groups.destroy');

// Campaigns & AI Studio
Route::get('/campaigns', [CampaignController::class, 'index'])->name('campaigns.index');
Route::get('/campaigns/create', [CampaignController::class, 'create'])->name('campaigns.create');
Route::post('/campaigns', [CampaignController::class, 'store'])->name('campaigns.store');
Route::post('/campaigns/ai-generate', [CampaignController::class, 'generateAi'])->name('campaigns.ai_generate');

// CSV & Brevo Importer
Route::get('/import', [ImportController::class, 'index'])->name('import.index');
Route::post('/import', [ImportController::class, 'process'])->name('import.process');

// Analytics & Metrics
Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');

// ML Brain & Search Trends
Route::get('/ml-brain', [MlBrainController::class, 'index'])->name('ml_brain.index');

// WooCommerce Sync
Route::get('/woocommerce', [WooCommerceController::class, 'index'])->name('woocommerce.index');
Route::post('/woocommerce/sync', [WooCommerceController::class, 'sync'])->name('woocommerce.sync');

// Settings & SMTP
Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
Route::post('/settings', [SettingController::class, 'store'])->name('settings.store');
Route::post('/settings/test-smtp', [SettingController::class, 'testSmtp'])->name('settings.test_smtp');
