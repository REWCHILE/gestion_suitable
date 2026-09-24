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
Route::get('/campaigns/preview', [CampaignController::class, 'preview'])->name('campaigns.preview');
Route::get('/campaigns/preview/html', [CampaignController::class, 'previewHtml'])->name('campaigns.preview_html');
Route::get('/campaigns/{campaign}/preview', [CampaignController::class, 'previewCampaign'])->name('campaigns.preview_campaign');
Route::get('/campaigns/{campaign}/html', [CampaignController::class, 'campaignHtml'])->name('campaigns.html');
Route::get('/campaigns/create', [CampaignController::class, 'create'])->name('campaigns.create');
Route::get('/campaigns/{campaign}/edit', [CampaignController::class, 'edit'])->name('campaigns.edit');
Route::put('/campaigns/{campaign}', [CampaignController::class, 'update'])->name('campaigns.update');
Route::post('/campaigns/{campaign}/update', [CampaignController::class, 'update']);
Route::post('/campaigns', [CampaignController::class, 'store'])->name('campaigns.store');
Route::post('/campaigns/ai-generate', [CampaignController::class, 'generateAi'])->name('campaigns.ai_generate');
Route::post('/campaigns/ai-generate-image', [CampaignController::class, 'generateAiImage'])->name('campaigns.ai_generate_image');
Route::post('/campaigns/ai-rewrite-section', [CampaignController::class, 'rewriteSection'])->name('campaigns.ai_rewrite_section');
Route::post('/campaigns/{campaign}/send', [CampaignController::class, 'send'])->name('campaigns.send');
Route::post('/campaigns/{campaign}/test-email', [CampaignController::class, 'sendTestEmail'])->name('campaigns.test_email');
Route::delete('/campaigns/{campaign}', [CampaignController::class, 'destroy'])->name('campaigns.destroy');

// CSV & Brevo Importer
Route::get('/import', [ImportController::class, 'index'])->name('import.index');
Route::get('/import/template', [ImportController::class, 'downloadTemplate'])->name('import.template');
Route::get('/import/export', [ImportController::class, 'exportClients'])->name('import.export');
Route::post('/import', [ImportController::class, 'process'])->name('import.process');

// Analytics & Metrics
Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');

// Cerebro Suitable (ML & Inteligencia de Negocio & Pensamientos AI)
Route::get('/ml-brain', [MlBrainController::class, 'index'])->name('ml_brain.index');
Route::get('/cerebro-suitable', [MlBrainController::class, 'index'])->name('cerebro_suitable.index');
Route::post('/ml-brain/diagnostic', [MlBrainController::class, 'diagnostic'])->name('ml_brain.diagnostic');
Route::get('/ml-brain/conversations', [MlBrainController::class, 'getConversations'])->name('ml_brain.conversations');
Route::post('/ml-brain/conversations', [MlBrainController::class, 'createConversation'])->name('ml_brain.create_conversation');
Route::get('/ml-brain/conversations/{id}', [MlBrainController::class, 'getConversation'])->name('ml_brain.get_conversation');
Route::post('/ml-brain/conversations/{id}/messages', [MlBrainController::class, 'sendMessage'])->name('ml_brain.send_message');
Route::delete('/ml-brain/conversations/{id}', [MlBrainController::class, 'deleteConversation'])->name('ml_brain.delete_conversation');

// WooCommerce Sync
Route::get('/woocommerce', [WooCommerceController::class, 'index'])->name('woocommerce.index');
Route::post('/woocommerce/sync', [WooCommerceController::class, 'sync'])->name('woocommerce.sync');

// Settings & SMTP
Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
Route::post('/settings', [SettingController::class, 'store'])->name('settings.store');
Route::post('/settings/test-smtp', [SettingController::class, 'testSmtp'])->name('settings.test_smtp');
Route::post('/settings/check-status', [SettingController::class, 'checkStatus'])->name('settings.check_status');
Route::post('/settings/ai-brand-assist', [SettingController::class, 'aiBrandAssist'])->name('settings.ai_brand_assist');
