<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Deal;
use GetCourse\Api\GetCourseClient;

class SyncEarnedValueCommand extends Command
{
    // Как мы будем вызывать команду в терминале
    protected $signature = 'deals:sync-earned-value';

    protected $description = 'Синхронизирует пропущенные earned_value для старых сделок из API GetCourse';

    public function handle()
    {
        // Ищем сделки с оплатой, но без чистой прибыли и с привязанным order_id
        $deals = Deal::where('payed_money', '>', 0)
            ->where(function ($query) {
                $query->where('earned_value', 0)
                    ->orWhereNull('earned_value');
            })
            ->whereNotNull('gc_order_id')
            ->whereNotNull('school_id')
            ->with('school') // Сразу подгружаем школу, чтобы не делать лишних SQL запросов
            ->get();

        if ($deals->isEmpty()) {
            $this->info('Отлично! Нет сделок для синхронизации, все данные актуальны.');
            return;
        }

        $this->info("Найдено сделок для обновления: {$deals->count()}");

        // Рисуем красивый прогресс-бар в консоли
        $bar = $this->output->createProgressBar($deals->count());
        $bar->start();

        $developerKey = config('services.getcourse.developer_key');

        if (!$developerKey) {
            $this->error('Не задан GETCOURSE_DEVELOPER_KEY в конфигах!');
            return;
        }

        foreach ($deals as $deal) {
            $school = $deal->school;

            if (!$school || !$school->getcourse_domain || !$school->getcourse_api_key) {
                $this->warn("\nПропущена сделка {$deal->id}: у школы нет доступов к API.");
                $bar->advance();
                continue;
            }

            // Инициализируем клиента
            $client = new GetCourseClient(
                $school->getcourse_domain,
                $developerKey,
                $school->getcourse_api_key
            );

            try {
                // Запрашиваем заказ по API
                $response = $client->deals()->getFields($deal->gc_order_id);

                if ($response->successful()) {
                    $earnedValue = $response->json('data.earned_value') ?? 0;

                    // Обновляем сделку
                    $deal->update([
                        'earned_value' => $earnedValue
                    ]);
                } else {
                    $this->warn("\nОшибка API для ГК заказа {$deal->gc_order_id}: Статус " . $response->status());
                }
            } catch (\Exception $e) {
                $this->error("\nСбой при обработке заказа {$deal->gc_order_id}: " . $e->getMessage());
            }

            // Микро-пауза 300 миллисекунд (чтобы не упереться в рейт-лимиты ГК - 3-5 запросов в секунду)
            usleep(300000);

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info('Синхронизация успешно завершена! Теперь суммы должны сойтись копейка в копейку.');
    }
}