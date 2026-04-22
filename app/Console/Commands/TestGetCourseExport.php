<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\School;
use Carbon\Carbon;

class TestGetCourseExport extends Command
{
    // Команда принимает ID школы (по умолчанию 1) и опцию --days (по умолчанию 7)
    protected $signature = 'gc:test-export {school_id=1} {--days=7}';
    protected $description = 'Тестовая выгрузка платежей из GetCourse Export API';

    public function handle()
    {
        $schoolId = $this->argument('school_id');
        $days = $this->option('days');

        $school = School::find($schoolId);
        if (!$school) {
            $this->error("Школа с ID {$schoolId} не найдена.");
            return;
        }

        // Берем доступы из таблицы schools (сверяясь со скрином)
        // Если названия колонок немного отличаются, поправь их здесь:
        $accountName = $school->getcourse_domain;
        $secretKey = $school->getcourse_api_key;

        if (empty($accountName) || empty($secretKey)) {
            $this->error("В настройках школы не заполнен аккаунт или API ключ GetCourse!");
            return;
        }

        $from = Carbon::now()->subDays($days)->format('Y-m-d');
        $to = Carbon::now()->format('Y-m-d');

        $this->info("Начинаем экспорт платежей школы '{$school->name}' с {$from} по {$to}...");

        // 1. ЗАПРАШИВАЕМ СОЗДАНИЕ ЭКСПОРТА
        $exportInitUrl = "https://{$accountName}/pl/api/account/payments";

        $response = Http::get($exportInitUrl, [
            'key' => $secretKey,
            'created_at' => [
                'from' => $from,
                'to' => $to,
            ],
        ]);

        if (!$response->successful() || !$response->json('success')) {
            $this->error("Ошибка при запросе экспорта!");
            $this->line($response->body());
            return;
        }

        $exportId = $response->json('info.export_id');
        $this->info("Задача на экспорт создана. ID задачи: {$exportId}");
        $this->line("Ждем сборки данных (ГетКурсу нужно время)...");

        // 2. ОПРАШИВАЕМ СТАТУС
        $exportResultUrl = "https://{$accountName}/pl/api/account/exports/{$exportId}";
        $attempts = 0;
        $maxAttempts = 90; // Ждем до 60 секунд
        $items = [];

        while ($attempts < $maxAttempts) {
            sleep(4); // Геткурс собирает файлы не мгновенно
            $attempts++;
            $this->line("Попытка {$attempts} из {$maxAttempts}...");

            $resultResponse = Http::get($exportResultUrl, ['key' => $secretKey]);
            $data = $resultResponse->json();

            // Если экспорт еще в процессе (Код 909 или текст "Файл еще не создан")
            if (isset($data['success']) && $data['success'] === false && (
                    ($data['error_code'] ?? null) == 909 ||
                    str_contains($data['error_message'] ?? '', 'Файл еще не создан') ||
                    str_contains($data['error_message'] ?? '', 'in progress')
                )) {
                continue;
            }

            // Если успешно отдали массив данных
            if (isset($data['success']) && $data['success'] === true && isset($data['info']['items'])) {
                $items = $data['info']['items'];
                $this->info("Данные успешно получены! Найдено платежей: " . count($items));
                break;
            }

            // Если вылезла ДРУГАЯ ошибка
            $this->error("Неизвестный ответ от сервера:");
            $this->line($resultResponse->body());
            return;
        }

        if (empty($items)) {
            $this->warn("Платежей за этот период не найдено или ГетКурс не успел собрать файл.");
            return;
        }

        // Выводим самый первый платеж для изучения
        $this->info("Структура первого платежа:");
        dump($items[0]);
    }
}