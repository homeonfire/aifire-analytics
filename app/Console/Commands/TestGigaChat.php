<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class TestGigaChat extends Command
{
    /**
     * Сигнатура команды для запуска из консоли
     * Пример: php artisan giga:test "Напиши код на PHP"
     */
    protected $signature = 'giga:test {prompt?}';
    protected $description = 'Тестовый запрос к GigaChat API с готовым Authorization Key';

    public function handle()
    {
        // ==========================================
        // 1. ВСТАВЬ СВОИ ДАННЫЕ СЮДА
        // ==========================================
        // Вставляем ту самую длинную строку из личного кабинета (Authorization Key)
        $authKey = 'ODZkYmJjZDYtZDJlZC00MTBjLTlmZTYtNDA4YTU1NTE3YmZhOjM1ZTYyYzM1LTI2ZDktNDg3NC1hMzYxLWY4MGEyZWMzZDgxMA==';

        // Scope для физлиц, как указано в документации
        $scope = 'GIGACHAT_API_PERS';

        $this->info("=== Запуск интеграции с GigaChat ===");

        // Генерация уникального RqUID в формате UUIDv4
        $rqUid = Str::uuid()->toString();

        $this->line("1. Запрос токена доступа...");
        $this->line("Сгенерированный RqUID: " . $rqUid);

        // --- ШАГ 1: ПОЛУЧЕНИЕ ТОКЕНА ---
        $authResponse = Http::asForm()
            ->withoutVerifying() // Отключаем проверку SSL Минцифры
            ->withHeaders([
                'Content-Type'  => 'application/x-www-form-urlencoded',
                'Accept'        => 'application/json',
                'RqUID'         => $rqUid,
                // Передаем готовый ключ без дополнительной кодировки
                'Authorization' => 'Basic ' . $authKey,
            ])
            ->post('https://ngw.devices.sberbank.ru:9443/api/v2/oauth', [
                'scope' => $scope,
            ]);

        if ($authResponse->failed()) {
            $this->error("❌ Ошибка авторизации (Код: {$authResponse->status()})");
            $this->line("Ответ сервера: " . $authResponse->body());
            return;
        }

        $accessToken = $authResponse->json('access_token');
        $this->info("✅ Токен доступа успешно получен!");

        // --- ШАГ 2: ЗАПРОС К НЕЙРОСЕТИ ---
        $prompt = $this->argument('prompt') ?? 'Привет, расскажи о себе в двух словах';
        $this->line("2. Отправка промпта: '{$prompt}'...");

        $chatResponse = Http::withHeaders([
            'Accept'        => 'application/json',
            'Authorization' => 'Bearer ' . $accessToken,
        ])
            ->withoutVerifying()
            ->post('https://gigachat.devices.sberbank.ru/api/v1/chat/completions', [
                'model' => 'GigaChat',
                'messages' => [
                    [
                        'role'    => 'system',
                        'content' => 'Ты — полезный ИИ-помощник.'
                    ],
                    [
                        'role'    => 'user',
                        'content' => $prompt
                    ]
                ],
                'stream' => false,
            ]);

        if ($chatResponse->failed()) {
            $this->error("❌ Ошибка генерации ответа (Код: {$chatResponse->status()})");
            $this->line("Ответ сервера: " . $chatResponse->body());
            return;
        }

        $result = $chatResponse->json();

        // --- ВЫВОД РЕЗУЛЬТАТА ---
        $this->newLine();
        $this->info("=== ОТВЕТ GIGACHAT ===");
        $this->line($result['choices'][0]['message']['content']);
        $this->newLine();
        $this->comment("Расход токенов: " . $result['usage']['total_tokens']);
    }
}