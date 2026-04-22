<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\School;
use App\Models\GcExport;
use Carbon\Carbon;

class RequestGcPayments extends Command
{
    protected $signature = 'gc:request-payments {school_id} {--days=1}';
    protected $description = 'Инициализирует экспорт платежей в GetCourse и пишет в БД';

    public function handle()
    {
        $schoolId = $this->argument('school_id');
        $days = $this->option('days');
        $school = School::find($schoolId);

        if (!$school) {
            $this->error("Школа не найдена.");
            return;
        }

        $accountName = $school->getcourse_domain;
        $secretKey = $school->getcourse_api_key;

        $from = Carbon::now()->subDays($days)->format('Y-m-d');
        $to = Carbon::now()->format('Y-m-d');

        $this->info("Запрашиваем экспорт с {$from} по {$to}...");

        $response = Http::get("https://{$accountName}/pl/api/account/payments", [
            'key' => $secretKey,
            'created_at' => ['from' => $from, 'to' => $to],
        ]);

        if (!$response->successful() || !$response->json('success')) {
            $this->error("Ошибка API GetCourse!");
            $this->line($response->body());
            return;
        }

        $exportId = $response->json('info.export_id');

        // Записываем в нашу базу, что мы ждем этот файл
        GcExport::create([
            'school_id' => $schoolId,
            'export_id' => $exportId,
            'date_from' => $from,
            'date_to' => $to,
            'status' => 'pending',
        ]);

        $this->info("Задача успешно добавлена в очередь! Export ID: {$exportId}");
    }
}