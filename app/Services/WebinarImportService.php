<?php

namespace App\Services;

use App\Models\Webinar;
use App\Models\UnifiedClient;
use App\Models\WebinarAttendance;
use App\Models\WebinarAttendanceInterval;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;

class WebinarImportService
{
    // ДОБАВИЛИ $schoolId В ПАРАМЕТРЫ
    public function importHtml(string $htmlContent, string $customTitle = null, ?string $cohort = null, ?int $schoolId = null)
    {
        $crawler = new Crawler($htmlContent);

        // 1. Собираем данные о вебинаре из шапки отчета
        $title = $customTitle ?? $crawler->filter('.room_title')->text('Без названия');
        $startTimeStr = $crawler->filter('.st-start')->text('');
        $duration = (int) $crawler->filter('.st-minutes')->text(0);

        $startedAt = null;
        if ($startTimeStr) {
            try {
                // Формат в Бизоне: "24.02.2026, 19:03"
                $startedAt = Carbon::createFromFormat('d.m.Y, H:i', trim($startTimeStr));
            } catch (\Exception $e) {
                Log::warning("Не удалось спарсить дату вебинара: " . $startTimeStr);
            }
        }

        // Передаем $schoolId внутрь транзакции
        return DB::transaction(function () use ($crawler, $title, $startedAt, $duration, $cohort, $schoolId) {

            // Изолируем вебинар СТРОГО по школе
            $webinar = Webinar::updateOrCreate(
                [
                    'title' => $title,
                    'started_at' => $startedAt,
                    'school_id' => $schoolId // Привязка к школе
                ],
                [
                    'duration_minutes' => $duration,
                    'room_id' => 'Bizon_HTML_Import',
                    'cohort' => $cohort
                ]
            );

            // 2. Парсим участников (блок .userItem)
            $crawler->filter('.userItem')->each(function (Crawler $node) use ($webinar, $schoolId) {
                // Извлекаем текст, очищая от лишних пробелов
                $email = trim($node->filter('.email')->text(''));
                $phoneRaw = trim($node->filter('.phone')->text(''));
                $phone = $phoneRaw ? preg_replace('/[^0-9]/', '', $phoneRaw) : null;
                $name = trim($node->filter('.username')->text('Неизвестно'));
                $city = trim($node->filter('.city')->text(''));

                if (empty($email) && empty($phone)) {
                    return;
                }

                // ВАЖНО: Ищем клиента СТРОГО в текущей школе!
                $client = UnifiedClient::where('school_id', $schoolId)
                    ->where(function ($query) use ($email, $phone) {
                        if ($email) $query->where('email', $email);
                        if ($phone) $query->orWhere('phone', $phone);
                    })
                    ->first();

                if (!$client) {
                    $client = UnifiedClient::create([
                        'school_id' => $schoolId, // Изолируем нового клиента
                        'email' => $email,
                        'phone' => $phone,
                        'first_name' => $name,
                        'city' => $city,
                    ]);
                }

                // Создание записи о посещении
                $attendance = WebinarAttendance::create([
                    'webinar_id' => $webinar->id,
                    'unified_client_id' => $client->id,
                    'city' => $city ?: $client->city,
                    'device' => $node->filter('.fa-mobile')->count() > 0 ? 'моб' : 'ПК',
                ]);

                $totalSeconds = 0;

                // 3. Парсинг интервалов
                $node->filter('.meter .item')->each(function (Crawler $intervalNode) use ($attendance, &$totalSeconds) {
                    $titleAttr = $intervalNode->attr('title');

                    if ($titleAttr && preg_match('/:\s*(.*?)\s*—\s*(.*)/u', $titleAttr, $matches)) {
                        $startStr = trim($matches[1]);
                        $endStr = trim($matches[2]);

                        $startSeconds = $this->convertBizonTimeToSeconds($startStr);
                        $endSeconds = $this->convertBizonTimeToSeconds($endStr);

                        $diffSeconds = max(0, $endSeconds - $startSeconds);
                        $totalSeconds += $diffSeconds;

                        WebinarAttendanceInterval::create([
                            'attendance_id' => $attendance->id,
                            'entered_at' => gmdate('H:i:s', $startSeconds),
                            'left_at' => gmdate('H:i:s', $endSeconds),
                            'minutes' => round($diffSeconds / 60),
                        ]);
                    }
                });

                // 4. Парсинг сообщений чата
                $node->filter('.userMessages > div')->each(function (Crawler $msgNode) use ($attendance) {
                    $timeNode = $msgNode->filter('span.text-primary');
                    $time = $timeNode->count() ? trim($timeNode->text()) : null;

                    $fullText = $msgNode->text();
                    $messageText = trim(str_replace((string)$time, '', $fullText));

                    if (!empty($messageText)) {
                        \App\Models\WebinarChatMessage::create([
                            'attendance_id' => $attendance->id,
                            'time' => $time,
                            'message' => $messageText,
                        ]);
                    }
                });

                // Обновляем итоговое время
                $attendance->update([
                    'total_minutes' => round($totalSeconds / 60)
                ]);
            });

            return $webinar;
        });
    }

    private function convertBizonTimeToSeconds(string $str): int
    {
        $h = 0; $m = 0; $s = 0;

        if (preg_match('/(\d+)\s*ч/u', $str, $matches)) {
            $h = (int)$matches[1];
        }
        if (preg_match('/(\d+)\s*мин/u', $str, $matches)) {
            $m = (int)$matches[1];
        }
        if (preg_match('/(\d+)\s*сек/u', $str, $matches)) {
            $s = (int)$matches[1];
        }

        return ($h * 3600) + ($m * 60) + $s;
    }
}