<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\School;
use App\Models\UnifiedClient;
use App\Models\Deal;
use App\Models\Product;
use App\Models\Manager;
use Carbon\Carbon;
use GetCourse\Api\GetCourseClient;
use App\Jobs\LinkManagerToDealJob; // <-- Подключили наш Job

class OrderWebhookController extends Controller
{
    public function handle(Request $request, $uuid)
    {
        Log::info("==========================================");
        Log::info("[1/8] Входящий вебхук заказов. UUID школы: {$uuid}");

        $school = School::where('uuid', $uuid)->first();
        if (!$school) {
            Log::error("[X] Школа с UUID {$uuid} не найдена!");
            return response()->json(['error' => 'School not found'], 404);
        }

        $data = $request->all();
        Log::info("[2/8] Школа найдена: {$school->name}. Данные вебхука:", $data);

        try {
            // 1. Клиент (First-touch UTM)
            Log::info("[3/8] Обработка клиента (email: " . ($data['email'] ?? 'нет') . ")");
            $client = UnifiedClient::updateOrCreate(
                [
                    'school_id' => $school->id,
                    'email' => $data['email'] ?? null,
                ],
                [
                    'phone' => $data['phone'] ?? null,
                    'first_name' => $data['first_name'] ?? null,
                    'last_name' => $data['last_name'] ?? null,
                    'getcourse_id' => $data['getcourse_id'] ?? null,
                    'city' => $data['city'] ?? null,
                    'avatar_url' => $data['avatar'] ?? null,
                    'sb_id' => $data['sb_id'] ?? null,
                ]
            );

            Log::info(" -> Клиент ID: {$client->id}. Новый: " . ($client->wasRecentlyCreated ? 'ДА' : 'НЕТ'));

            // Записываем UTM клиента ТОЛЬКО при его создании
            if ($client->wasRecentlyCreated) {
                // Если нет пользовательских UTM, берем UTM заказа как точку входа
                $client->update([
                    'utm_source' => !empty($data['utm_source']) ? $data['utm_source'] : ($data['deal_utm_source'] ?? null),
                    'utm_medium' => !empty($data['utm_medium']) ? $data['utm_medium'] : ($data['deal_utm_medium'] ?? null),
                    'utm_campaign' => !empty($data['utm_campaign']) ? $data['utm_campaign'] : ($data['deal_utm_campaign'] ?? null),
                    'utm_term' => !empty($data['utm_term']) ? $data['utm_term'] : ($data['deal_utm_term'] ?? null),
                    'utm_content' => !empty($data['utm_content']) ? $data['utm_content'] : ($data['deal_utm_content'] ?? null),
                ]);
                Log::info(" -> Записаны First-touch UTM клиента (с фоллбэком на UTM сделки, если нужно)");
            }

            // 2. Ищем или создаем менеджера (если ГК вдруг передал его email)
            $managerId = null;
            if (!empty($data['manager_email'])) {
                Log::info("[4/8] Обработка менеджера из вебхука: {$data['manager_email']}");
                $manager = Manager::updateOrCreate(
                    [
                        'school_id' => $school->id,
                        'email' => $data['manager_email'],
                    ],
                    [
                        'name' => $data['manager_name'] ?? 'Неизвестно',
                        'phone' => $data['manager_phone'] ?? null,
                    ]
                );
                $managerId = $manager->id;
                Log::info(" -> Менеджер ID: {$managerId}");
            } else {
                Log::info("[4/8] Менеджер не передан в вебхуке. Будем искать по API.");
            }

            // Очищаем стоимость и оплаты от текста (например "1500 руб." -> 1500)
            $cost = isset($data['cost']) ? (float) preg_replace('/[^0-9.]/', '', str_replace(',', '.', $data['cost'])) : 0;
            $payed = isset($data['payed_money']) ? (float) preg_replace('/[^0-9.]/', '', str_replace(',', '.', $data['payed_money'])) : 0;

            // 3. Создаем или обновляем сделку (Last-touch UTM & Promocode)
            Log::info("[5/8] Обработка сделки (GC номер: " . ($data['gc_number'] ?? 'нет') . ")");
            $deal = Deal::updateOrCreate(
                [
                    'school_id' => $school->id,
                    'gc_number' => $data['gc_number'] ?? null,
                ],
                [
                    'unified_client_id' => $client->id,
                    'manager_id' => $managerId,
                    'gc_order_id' => $data['order_id'] ?? null, // <--- СОХРАНЯЕМ ID ЗАКАЗА ДЛЯ API
                    'status' => $data['status'] ?? 'new',
                    'cost' => $cost,
                    'payed_money' => $payed,

                    'gc_created_at' => !empty($data['created_at']) ? Carbon::parse($data['created_at']) : null,
                    'gc_paid_at' => !empty($data['payed_at']) ? Carbon::parse($data['payed_at']) : null,

                    'promocode' => $data['promocode'] ?? null,
                    'utm_source' => $data['deal_utm_source'] ?? null,
                    'utm_medium' => $data['deal_utm_medium'] ?? null,
                    'utm_campaign' => $data['deal_utm_campaign'] ?? null,
                    'utm_term' => $data['deal_utm_term'] ?? null,
                    'utm_content' => $data['deal_utm_content'] ?? null,
                ]
            );
            Log::info(" -> Сделка ID: {$deal->id}, Статус: {$deal->status}");

            // 4. Ленивая загрузка предложений (Offers)
            if (!empty($data['offers'])) {
                Log::info("[6/8] Обработка тарифов (offers): {$data['offers']}");
                $offerIds = array_filter(array_map('trim', explode(',', $data['offers'])));
                $productIds = [];

                $gcClient = null;

                foreach ($offerIds as $offerId) {
                    Log::info(" -> Проверяем тариф ID: {$offerId}");

                    $product = Product::where('school_id', $school->id)
                        ->where('getcourse_id', $offerId)
                        ->first();

                    if (!$product) {
                        Log::info(" -> Тариф {$offerId} не найден в БД. Пробуем получить по API...");
                        $title = "Неизвестный тариф (ID: {$offerId})";
                        $price = 0;

                        if (!$gcClient && $school->getcourse_domain && $school->getcourse_api_key) {
                            $developerKey = config('services.getcourse.developer_key');
                            if ($developerKey) {
                                Log::info(" -> Инициализация клиента GetCourse...");
                                $gcClient = new GetCourseClient(
                                    $school->getcourse_domain,
                                    $developerKey,
                                    $school->getcourse_api_key
                                );
                            } else {
                                Log::warning(" -> Не задан GETCOURSE_DEVELOPER_KEY!");
                            }
                        }

                        if ($gcClient) {
                            try {
                                $offerResponse = $gcClient->offers()->getById((int) $offerId);
                                if ($offerResponse->successful()) {
                                    $responseJson = $offerResponse->json();

                                    if (!empty($responseJson['data']) && isset($responseJson['data'][0])) {
                                        $offerData = $responseJson['data'][0];
                                        $title = $offerData['title'] ?? $title;
                                        $price = $offerData['price'] ?? 0;
                                        Log::info(" -> Успешно получено по API: {$title} (Цена: {$price})");
                                    } else {
                                        Log::warning(" -> API ГК вернул успешный статус, но массив data пуст.");
                                    }
                                } else {
                                    Log::error(" -> Ошибка API ГК для тарифа {$offerId}: Статус " . $offerResponse->status());
                                }
                            } catch (\Exception $e) {
                                Log::error(" -> Исключение при запросе к API ГК: " . $e->getMessage());
                            }
                        } else {
                            Log::warning(" -> Клиент ГК не проинициализирован (нет ключей). Сохраняем заглушку.");
                        }

                        // Создаем продукт в базе
                        $product = Product::create([
                            'school_id' => $school->id,
                            'getcourse_id' => $offerId,
                            'title' => $title,
                            'price' => $price,
                            'category' => null,
                            'cohort' => null,
                        ]);
                        Log::info(" -> Создан новый продукт в БД (ID: {$product->id})");
                    } else {
                        Log::info(" -> Тариф {$offerId} найден в БД (Продукт ID: {$product->id})");
                    }

                    $productIds[] = $product->id;
                }

                // Синхронизируем продукты со сделкой (pivot)
                if (!empty($productIds)) {
                    Log::info("[7/8] Привязка продуктов к сделке. ID продуктов: " . implode(', ', $productIds));
                    $deal->products()->sync($productIds);
                } else {
                    Log::warning("[7/8] Нет продуктов для привязки к сделке.");
                }
            } else {
                Log::info("[6/8] Поле 'offers' пустое, пропускаем этап тарифов.");
            }

            // 5. Запуск фоновой задачи на поиск менеджера
            if (!empty($data['order_id'])) {
                Log::info("[8/8] Отправка фоновой задачи на поиск менеджера для заказа: {$data['order_id']}");
                LinkManagerToDealJob::dispatch($deal);
            } else {
                Log::warning("[8/8] В вебхуке нет 'order_id', невозможно запустить поиск менеджера по API.");
            }

            Log::info("[V] Вебхук успешно обработан!");
            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            Log::error("[X] КРИТИЧЕСКАЯ ОШИБКА ОБРАБОТКИ ВЕБХУКА: " . $e->getMessage());
            Log::error($e->getTraceAsString());
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }
}