<?php

namespace App\Imports;

use App\Services\WebinarImportService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class WebinarAttendanceImport implements ToCollection, WithChunkReading
{
    public function __construct(
        protected WebinarImportService $service,
        protected string $webinarTitle
    ) {}

    public function collection(Collection $rows)
    {
        $data = $rows->toArray();

        // Убираем первую техническую строку Бизона ("МАРАФОН...")
        if (isset($data[0][0]) && str_contains((string)$data[0][0], 'МАРАФОН')) {
            array_shift($data);
        }

        // Заголовки (Дата, Имя зрителя...)
        $columns = array_shift($data);

        if (!$columns || empty($data)) return;

        $formattedRows = [];
        foreach ($data as $row) {
            if (count($columns) === count($row)) {
                $formattedRows[] = array_combine($columns, $row);
            }
        }

        if (!empty($formattedRows)) {
            $this->service->import($formattedRows, $this->webinarTitle, now());
        }
    }

    public function chunkSize(): int
    {
        return 100;
    }
}