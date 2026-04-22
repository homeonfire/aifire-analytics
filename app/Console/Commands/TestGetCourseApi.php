<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\School;
use GetCourse\Api\GetCourseClient;

class TestGetCourseApi extends Command
{
    // Команда принимает обязательный ID школы и опциональный ID сделки
    protected $signature = 'gc:test {school_id} {deal_id?}';

    protected $description = 'Тестирование нового API GetCourse для сделок (основные + доп. поля)';

    public function handle()
    {
        $schoolId = $this->argument('school_id');
        $dealId = $this->argument('deal_id');

        $school = School::find($schoolId);

        if (!$school) {
            $this->error("Школа с ID {$schoolId} не найдена в базе.");
            return;
        }

        if (!$school->getcourse_domain || !$school->getcourse_api_key) {
            $this->error("У школы '{$school->name}' не настроены домен или API ключ.");
            return;
        }

        $developerKey = config('services.getcourse.developer_key');

        if (!$developerKey) {
            $this->error("Не задан ключ разработчика GETCOURSE_DEVELOPER_KEY в .env");
            return;
        }

        $this->info("Подключаемся к {$school->getcourse_domain}...");

        try {
            // Инициализируем клиент библиотеки
            $client = new GetCourseClient(
                $school->getcourse_domain,
                $developerKey,
                $school->getcourse_api_key
            );

            // Если передан ID сделки - запрашиваем её поля
            if ($dealId) {
                // 1. ЗАПРАШИВАЕМ ОСНОВНЫЕ ПОЛЯ
                $this->info("\n--- 1. Запрашиваем ОСНОВНЫЕ данные по сделке #{$dealId} ---");
                $response = $client->deals()->getFields((int) $dealId);

                if ($response->successful()) {
                    $this->info("✅ Успешный ответ API (Основные поля):");
                    $this->line(json_encode($response->json(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                } else {
                    $this->error("❌ Ошибка API! HTTP Статус: " . $response->status());
                    $this->line(json_encode($response->json(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                }

                // 2. ЗАПРАШИВАЕМ ДОПОЛНИТЕЛЬНЫЕ (КАСТОМНЫЕ) ПОЛЯ
                $this->info("\n--- 2. Запрашиваем ДОП. ПОЛЯ (Custom Fields) по сделке #{$dealId} ---");
                $customFieldsResponse = $client->deals()->getCustomFields((int) $dealId);

                if ($customFieldsResponse->successful()) {
                    $this->info("✅ Успешный ответ API (Доп. поля):");
                    $this->line(json_encode($customFieldsResponse->json(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                } else {
                    $this->error("❌ Ошибка API (Доп. поля)! HTTP Статус: " . $customFieldsResponse->status());
                    $this->line(json_encode($customFieldsResponse->json(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                }

            } else {
                // Если ID сделки нет - делаем легкий запрос для проверки связи
                $this->info("ID сделки не передан. Запрашиваем системные теги сделок (проверка связи)...");
                $response = $client->deals()->getDealsTags();

                if ($response->successful()) {
                    $this->info("✅ Успешный ответ API:");
                    $this->line(json_encode($response->json(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                } else {
                    $this->error("❌ Ошибка API! HTTP Статус: " . $response->status());
                    $this->line(json_encode($response->json(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                }
            }

        } catch (\Exception $e) {
            $this->error("Критическая ошибка: " . $e->getMessage());
        }
    }
}