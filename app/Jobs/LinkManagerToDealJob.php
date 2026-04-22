<?php

namespace App\Jobs;

use App\Models\Deal;
use App\Models\Manager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use GetCourse\Api\GetCourseClient;

class LinkManagerToDealJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Deal $deal
    ) {}

    public function handle(): void
    {
        // 1. Проверяем наличие всех данных
        if (!$this->deal->gc_order_id || !$this->deal->school) {
            return;
        }

        $school = $this->deal->school;

        // 2. Берем ключи
        $domain = $school->getcourse_domain;
        $apiKey = $school->getcourse_api_key;
        $developerKey = config('services.getcourse.developer_key');

        if (!$domain || !$apiKey || !$developerKey) {
            Log::error("LinkManagerJob: У школы {$school->name} или в конфиге не указаны доступы к API.");
            return;
        }

        // 3. Инициализируем клиент
        $client = new GetCourseClient($domain, $developerKey, $apiKey);

        // 4. Запрашиваем информацию о заказе
        $orderResponse = $client->deals()->getFields($this->deal->gc_order_id);

        if ($orderResponse->failed()) {
            Log::error("LinkManagerJob: Ошибка API при поиске заказа {$this->deal->gc_order_id}. Ответ: " . $orderResponse->body());
            return;
        }

        // --- НОВЫЙ БЛОК: СОХРАНЯЕМ EARNED VALUE ---
        // Берем чистую прибыль из ответа (если её нет, ставим 0)
        $earnedValue = $orderResponse->json('data.earned_value') ?? 0;

        $this->deal->update([
            'earned_value' => $earnedValue
        ]);
        Log::info("LinkManagerJob: Для сделки {$this->deal->id} обновлена чистая прибыль (earned_value): {$earnedValue}");
        // ------------------------------------------

        // 5. Достаем ID менеджера
        $managerUserId = $orderResponse->json('data.manager_user_id');

        if ($managerUserId) {
            // 6. Запрашиваем данные пользователя-менеджера
            $userResponse = $client->users()->getFields($managerUserId);

            if ($userResponse->successful()) {
                $userData = $userResponse->json('data');

                $firstName = $userData['first_name'] ?? '';
                $lastName = $userData['last_name'] ?? '';
                $fullName = trim($firstName . ' ' . $lastName) ?: 'Неизвестный менеджер';

                // 7. Ищем или создаем менеджера
                $manager = \App\Models\Manager::updateOrCreate(
                    [
                        'school_id' => $school->id,
                        'getcourse_id' => $managerUserId
                    ],
                    [
                        'name' => $fullName,
                        'email' => $userData['email'] ?? null,
                        'phone' => $userData['phone'] ?? null,
                    ]
                );

                // 8. Привязываем менеджера
                $this->deal->update([
                    'manager_id' => $manager->id,
                    'manager_name' => $manager->name,
                ]);

                Log::info("LinkManagerJob: Менеджер {$manager->name} ({$managerUserId}) привязан к сделке {$this->deal->id}");
            }
        }
    }
}