<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Deal;
use Carbon\Carbon;

class RevenueChart extends ChartWidget
{
    protected static bool $isDiscovered = false;
    protected static ?string $heading = 'Динамика выручки (последние 6 месяцев)';
    protected static ?int $sort = 2; // Выводим вторым блоком
    protected static bool $isLazy = false;

    protected function getData(): array
    {
        $data = [];
        $labels = [];

        // Собираем стату за 6 месяцев назад
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            // Формируем подпись: Янв, Фев и тд.
            $labels[] = $month->translatedFormat('M Y');

            // Считаем деньги за этот месяц
            $sum = Deal::whereYear('gc_created_at', $month->year)
                ->whereMonth('gc_created_at', $month->month)
                ->sum('payed_money');

            $data[] = $sum;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Выручка (₽)',
                    'data' => $data,
                    'fill' => 'start', // Закрашивает область под линией - выглядит очень стильно
                    'backgroundColor' => 'rgba(34, 197, 94, 0.2)', // Зеленоватый оттенок
                    'borderColor' => '#22c55e',
                    'tension' => 0.4, // Делает линию плавной, а не угловатой
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}