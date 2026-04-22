<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateDataToSupabase extends Command
{
    protected $signature = 'db:migrate-data';
    protected $description = 'Умный перенос данных из MySQL в Postgres с авто-разрешением связей';

    public function handle()
    {
        $this->info('🚀 Запускаем умный перенос данных...');

        $tables = array_map('current', DB::connection('mysql')->select('SHOW TABLES'));
        $tables = array_diff($tables, ['migrations']);

        // 1. Тотальная очистка Postgres перед заливкой (на случай, если там остались куски от прошлых попыток)
        $this->info('🧹 Очищаем базу Postgres перед переносом...');
        $tableList = implode(', ', array_map(function($t) { return '"'.$t.'"'; }, $tables));
        DB::connection('pgsql')->statement("TRUNCATE {$tableList} CASCADE");

        // 2. Умный цикл с ретраями
        $pendingTables = $tables;
        $maxRetries = 10; // 10 проходов хватит, чтобы распутать связи любой вложенности

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            if (empty($pendingTables)) {
                break; // Если пустых не осталось — выходим
            }

            $this->info("\n=== Проход {$attempt} (Осталось таблиц: " . count($pendingTables) . ") ===");
            $failedTables = [];

            foreach ($pendingTables as $table) {
                $this->info("Читаем таблицу: {$table}...");

                try {
                    $data = DB::connection('mysql')->table($table)->get();
                    $count = $data->count();

                    if ($count === 0) {
                        $this->warn("Таблица пуста. Пропускаем.");
                        continue;
                    }

                    // Конвертируем объекты в массивы
                    $insertData = json_decode(json_encode($data), true);
                    $chunks = array_chunk($insertData, 500);

                    $this->output->progressStart(count($chunks));

                    foreach ($chunks as $chunk) {
                        DB::connection('pgsql')->table($table)->insert($chunk);
                        $this->output->progressAdvance();
                    }

                    $this->output->progressFinish();
                    $this->info("✅ Перенесено записей: {$count}");

                } catch (\Illuminate\Database\QueryException $e) {
                    // Код 23503 — это ошибка нарушения внешнего ключа в Postgres
                    if ($e->getCode() == '23503') {
                        $this->warn("⚠️ Ждет родительскую таблицу. Откладываем на следующий проход.");
                        $failedTables[] = $table;
                        
                        // Сносим то, что успело залиться до падения (чтобы при ретрае не было дублей)
                        DB::connection('pgsql')->statement("TRUNCATE TABLE \"{$table}\" CASCADE");
                    } else {
                        // Если ошибка другая (например, несовпадение типов) - останавливаемся и смотрим
                        $this->error("❌ Критическая ошибка в таблице {$table}: " . $e->getMessage());
                        return;
                    }
                }
            }

            // На следующий проход отправляем только те таблицы, которые не смогли залиться
            $pendingTables = $failedTables;
        }

        if (!empty($pendingTables)) {
            $this->error("\n❌ Не удалось перенести таблицы из-за сложных циклических связей: " . implode(', ', $pendingTables));
        } else {
            $this->info("\n🚀 Успех! Все данные идеально легли в Postgres.");
        }
    }
}