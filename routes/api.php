<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\OrderWebhookController;
use App\Http\Controllers\Api\TildaWebhookController;
use App\Http\Controllers\GigaProxyController;

Route::any('/webhooks/{school_uuid}/getcourse/users', [WebhookController::class, 'importUsersFromGC']);
Route::post('/webhooks/{uuid}/getcourse/orders', [App\Http\Controllers\OrderWebhookController::class, 'handle']);Route::post('/webhooks/{school_uuid}/tilda', [TildaWebhookController::class, 'handle']); // <-- Добавили UUID сюда
Route::post('/giga-proxy', [GigaProxyController::class, 'handle']);
Route::post('/getcourse/test-webhook', function (Request $request) {
    // Пишем все пришедшие данные в laravel.log
    Log::info('--- ТЕСТОВЫЙ ВЕБХУК ОТ GETCOURSE ---');
    Log::info($request->all());

    // Обязательно отдаем 200 OK, чтобы ГК не пытался слать повторно
    return response()->json(['status' => 'success']);
});