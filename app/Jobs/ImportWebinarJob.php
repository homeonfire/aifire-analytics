<?php

namespace App\Jobs;

use App\Services\WebinarImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ImportWebinarJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600;

    public function __construct(
        protected string $filePath,
        protected ?string $webinarTitle = null,
        protected ?string $cohort = null,
        protected ?int $schoolId = null // <--- ДОБАВИЛИ ID ШКОЛЫ
    ) {}

    public function handle(WebinarImportService $service)
    {
        Log::info("--- ЗАПУСК ИМПОРТА ---");
        Log::info("Файл: {$this->filePath}");
        Log::info("Школа ID: " . ($this->schoolId ?? 'ПУСТО'));
        Log::info("Поток (cohort): " . ($this->cohort ?? 'ПУСТО'));

        if (!Storage::disk('public')->exists($this->filePath)) {
            Log::error("ОШИБКА: Файл не найден: {$this->filePath}");
            return;
        }

        $htmlContent = Storage::disk('public')->get($this->filePath);

        // Передаем школу в сервис
        $webinar = $service->importHtml($htmlContent, $this->webinarTitle, $this->cohort, $this->schoolId);

        Log::info("Сохраненный вебинар ID: {$webinar->id}, Поток: " . ($webinar->cohort ?? 'ПУСТО'));

        Storage::disk('public')->delete($this->filePath);
    }
}