<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage; // <-- Добавили фасад для работы с файлами
use App\Models\GcExport;
use App\Models\Payment;
use App\Models\Deal;
use Carbon\Carbon;

class ProcessGcExports extends Command
{
    protected $signature = 'gc:process-exports';
    protected $description = 'Проверяет статус экспортов и скачивает готовые платежи';

    public function handle()
    {
        // Ищем все задачи, которые еще в ожидании
        $pendingExports = GcExport::with('school')->where('status', 'pending')->get();

        if ($pendingExports->isEmpty()) {
            $this->info("Нет ожидающих экспортов.");
            return;
        }

        foreach ($pendingExports as $export) {
            $checkMessage = "Проверяем экспорт ID: {$export->export_id} (Школа: {$export->school->name})...";
            $this->line($checkMessage);
            Log::info("[GC Export] " . $checkMessage);

            $accountName = $export->school->getcourse_domain;
            $secretKey = $export->school->getcourse_api_key;

            $resultResponse = Http::get("https://{$accountName}/pl/api/account/exports/{$export->export_id}", [
                'key' => $secretKey
            ]);

            $res = $resultResponse->json();

            // Если экспорт еще собирается
            if (isset($res['success']) && $res['success'] === false) {
                if (($res['error_code'] ?? null) == 909 || str_contains($res['error_message'] ?? '', 'еще не создан') || str_contains($res['error_message'] ?? '', 'in progress')) {
                    $this->warn("Экспорт {$export->export_id} еще в процессе. Оставляем на следующую минуту.");
                    continue;
                } else {
                    // Какая-то фатальная ошибка от ГК
                    $errorMsg = $res['error_message'] ?? 'Неизвестная ошибка';
                    $export->update(['status' => 'failed', 'error_message' => $errorMsg]);

                    $this->error("Экспорт {$export->export_id} завершился с ошибкой.");
                    Log::error("[GC Export] Экспорт {$export->export_id} завершился с ошибкой: {$errorMsg}");
                    continue;
                }
            }

            // Если файл ГОТОВ
            if (isset($res['success']) && $res['success'] === true && isset($res['info']['items'])) {
                $this->info("Экспорт готов! Сохраняем сырой файл...");

                // --- СОХРАНЯЕМ СЫРОЙ JSON В ПАПКУ ---
                // Файл появится по пути: storage/app/getcourse_exports/export_12345678.json
                $fileName = "getcourse_exports/export_{$export->export_id}.json";

                // Используем JSON с красивым форматированием (JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                // чтобы его было удобно читать глазами
                Storage::put($fileName, json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

                $this->line("Сырой файл успешно сохранен: storage/app/{$fileName}");
                Log::info("[GC Export] Сырой файл сохранен: storage/app/{$fileName}");
                // ------------------------------------

                $this->info("Импортируем данные в БД...");
                $this->importPayments($res['info']['items'], $export->school_id);

                // Отмечаем, что всё скачано
                $export->update(['status' => 'completed']);

                $successMessage = "Экспорт {$export->export_id} успешно обработан.";
                $this->info($successMessage);
                Log::info("[GC Export] " . $successMessage);
            }
        }
    }

    // Логика импорта
    private function importPayments(array $items, int $schoolId)
    {
        $added = 0;
        $updated = 0;

        foreach ($items as $row) {
            if (!is_numeric($row[0])) continue;

            $gcPaymentId = $row[0];
            $clientName = $row[1] ?? null;
            $clientEmail = $row[2] ?? null;
            $gcDealNumber = $row[3] ?? null;
            $gcCreatedAt = $row[4] ? Carbon::parse($row[4]) : null;
            $paymentSystem = $row[5] ?? null;
            $status = $row[6] ?? null;

            $cleanMoney = function($str) {
                return (float) preg_replace('/[^\d.]/', '', str_replace(',', '.', $str));
            };

            $amount = isset($row[7]) ? $cleanMoney($row[7]) : 0;
            $commission = isset($row[8]) ? $cleanMoney($row[8]) : 0;
            $netAmount = isset($row[9]) ? $cleanMoney($row[9]) : 0;
            $operationId = $row[10] ?? null;
            $offerName = $row[11] ?? null;

            $dealId = null;
            if ($gcDealNumber) {
                $deal = Deal::where('school_id', $schoolId)->where('gc_number', $gcDealNumber)->first();
                if ($deal) $dealId = $deal->id;
            }

            $payment = Payment::updateOrCreate(
                ['gc_payment_id' => $gcPaymentId],
                [
                    'school_id' => $schoolId,
                    'deal_id' => $dealId,
                    'client_name' => $clientName,
                    'client_email' => $clientEmail,
                    'gc_deal_number' => $gcDealNumber,
                    'gc_created_at' => $gcCreatedAt,
                    'payment_system' => $paymentSystem,
                    'status' => $status,
                    'amount' => $amount,
                    'commission_amount' => $commission,
                    'net_amount' => $netAmount,
                    'operation_id' => $operationId,
                    'offer_name' => $offerName,
                ]
            );

            $payment->wasRecentlyCreated ? $added++ : $updated++;
        }

        $resultMessage = "Импорт завершен: {$added} новых, {$updated} обновлено.";
        $this->line($resultMessage);
        Log::info("[GC Export] " . $resultMessage);
    }
}