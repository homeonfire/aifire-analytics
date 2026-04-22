<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\School;
use GetCourse\Api\GetCourseClient;

class TestGetCourseOfferApi extends Command
{
    // Принимает ID школы и обязательный ID оффера (предложения)
    protected $signature = 'gc:test-offer {school_id} {offer_id}';

    protected $description = 'Тестирование нового API GetCourse: получение конкретного предложения (оффера)';

    public function handle()
    {
        $schoolId = $this->argument('school_id');
        $offerId = $this->argument('offer_id');

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
            $client = new GetCourseClient(
                $school->getcourse_domain,
                $developerKey,
                $school->getcourse_api_key
            );

            $this->info("\n--- Запрашиваем Предложение (Offer) #{$offerId} ---");

            // Вызываем метод получения оффера по ID
            $response = $client->offers()->getById((int) $offerId);

            if ($response->successful()) {
                $this->info("✅ Успешный ответ API:");
                $this->line(json_encode($response->json(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            } else {
                $this->error("❌ Ошибка API! HTTP Статус: " . $response->status());
                $this->line(json_encode($response->json(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }

        } catch (\Exception $e) {
            $this->error("Критическая ошибка: " . $e->getMessage());
        }
    }
}