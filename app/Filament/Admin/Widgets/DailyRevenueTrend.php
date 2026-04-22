<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Deal;
use App\Models\Launch;
use Carbon\Carbon;
use Livewire\Attributes\On;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;

class DailyRevenueTrend extends ChartWidget
{
    protected static ?string $heading = 'Динамика выручки (по дате создания заявки)';
    protected int | string | array $columnSpan = 'full';
    protected static ?string $maxHeight = '300px';
    protected static bool $isDiscovered = false;
    protected static bool $isLazy = false;

    public ?int $launchId = null;

    public function mount(): void
    {
        $schoolId = Filament::getTenant()?->id;
        $defaultLaunch = Launch::where('school_id', $schoolId)->latest()->first();
        $this->launchId = $defaultLaunch?->id;
    }

    #[On('updateLaunchFilters')]
    public function updateLaunchFilters($launchId): void
    {
        $this->launchId = $launchId;
    }

    protected function getData(): array
    {
        $schoolId = Filament::getTenant()?->id;
        $launch = Launch::find($this->launchId);

        if (!$launch || !$schoolId) {
            return ['datasets' => [], 'labels' => []];
        }

        // Вытягиваем только ОПЛАЧЕННЫЕ сделки запуска с учетом окон конверсии
        $paidDeals = Deal::where('school_id', $schoolId)
            ->where('payed_money', '>', 0)
            ->where(function (Builder $q) use ($launch) {

                // 1. РЕГИСТРАЦИИ (Если вдруг они платные)
                $q->orWhere(function (Builder $subQ) use ($launch) {
                    $subQ->whereHas('products', function(Builder $p) use ($launch) {
                        $p->whereIn('category', ['Регистрация', 'Регистрация на вебинар', 'Лид-магнит'])
                            ->whereHas('launches', fn($l) => $l->where('launches.id', $launch->id));
                    });
                });

                // 2. ТРИПВАЕРЫ
                $q->orWhere(function (Builder $subQ) use ($launch) {
                    $subQ->whereHas('products', function(Builder $p) use ($launch) {
                        $p->where('category', 'Трипваер')
                            ->whereHas('launches', fn($l) => $l->where('launches.id', $launch->id));
                    });
                    if ($launch->tripwire_start) $subQ->where('gc_created_at', '>=', $launch->tripwire_start);
                    if ($launch->tripwire_end) $subQ->where('gc_created_at', '<=', $launch->tripwire_end);
                });

                // 3. БРОНИРОВАНИЯ
                $q->orWhere(function (Builder $subQ) use ($launch) {
                    $subQ->whereHas('products', function(Builder $p) use ($launch) {
                        $p->where('category', 'Бронирование')
                            ->whereHas('launches', fn($l) => $l->where('launches.id', $launch->id));
                    });
                    if ($launch->booking_start) $subQ->where('gc_created_at', '>=', $launch->booking_start);
                    if ($launch->booking_end) $subQ->where('gc_created_at', '<=', $launch->booking_end);
                });

                // 4. ФЛАГМАНЫ И КЛУБЫ
                $q->orWhere(function (Builder $subQ) use ($launch) {
                    $subQ->whereHas('products', function(Builder $p) use ($launch) {
                        $p->whereNotIn('category', ['Регистрация', 'Регистрация на вебинар', 'Лид-магнит', 'Трипваер', 'Бронирование'])
                            ->whereHas('launches', fn($l) => $l->where('launches.id', $launch->id));
                    });
                    if ($launch->flagship_start) $subQ->where('gc_created_at', '>=', $launch->flagship_start);
                    if ($launch->flagship_end) $subQ->where('gc_created_at', '<=', $launch->flagship_end);
                });

            })
            ->get();

        $dailyData = $paidDeals->groupBy(function($deal) {
            // Берем дату из ГетКурса, если нет - системную
            $date = $deal->gc_created_at ?? $deal->created_at;
            return $date ? Carbon::parse($date)->format('Y-m-d') : 'unknown';
        })
            ->map(fn($dayDeals) => $dayDeals->sum('payed_money'))
            ->sortBy(fn($val, $key) => $key); // Сортируем хронологически

        return [
            'datasets' => [
                [
                    'label' => 'Принесли заявки этого дня (₽)',
                    'data' => $dailyData->values()->toArray(),
                    'fill' => 'start',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'borderColor' => '#22c55e',
                    'tension' => 0.4
                ],
            ],
            'labels' => $dailyData->keys()->map(fn($date) => $date !== 'unknown' ? Carbon::parse($date)->format('d.m') : 'Нет даты')->toArray(),
        ];
    }

    protected function getType(): string {
        return 'line';
    }
}