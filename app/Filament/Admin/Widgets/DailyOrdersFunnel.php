<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Deal;
use App\Models\Launch;
use Carbon\Carbon;
use Livewire\Attributes\On;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;

class DailyOrdersFunnel extends ChartWidget
{
    protected static ?string $heading = 'Воронка заявок и оплат по дням';
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

        $allDeals = Deal::where('school_id', $schoolId)
            ->where(function (Builder $q) use ($launch) {

                // 1. ТРИПВАЕРЫ
                $q->orWhere(function (Builder $subQ) use ($launch) {
                    $subQ->whereHas('products', function(Builder $p) use ($launch) {
                        $p->where('category', 'Трипваер')
                            ->whereHas('launches', fn($l) => $l->where('launches.id', $launch->id));
                    });
                    if ($launch->tripwire_start) $subQ->where('gc_created_at', '>=', $launch->tripwire_start);
                    if ($launch->tripwire_end) $subQ->where('gc_created_at', '<=', $launch->tripwire_end);
                });

                // 2. БРОНИРОВАНИЯ
                $q->orWhere(function (Builder $subQ) use ($launch) {
                    $subQ->whereHas('products', function(Builder $p) use ($launch) {
                        $p->where('category', 'Бронирование')
                            ->whereHas('launches', fn($l) => $l->where('launches.id', $launch->id));
                    });
                    if ($launch->booking_start) $subQ->where('gc_created_at', '>=', $launch->booking_start);
                    if ($launch->booking_end) $subQ->where('gc_created_at', '<=', $launch->booking_end);
                });

                // 3. ФЛАГМАНЫ И КЛУБЫ
                $q->orWhere(function (Builder $subQ) use ($launch) {
                    $subQ->whereHas('products', function(Builder $p) use ($launch) {
                        $p->whereIn('category', ['Флагман', 'Клуб по подписке'])
                            ->whereHas('launches', fn($l) => $l->where('launches.id', $launch->id));
                    });
                    if ($launch->flagship_start) $subQ->where('gc_created_at', '>=', $launch->flagship_start);
                    if ($launch->flagship_end) $subQ->where('gc_created_at', '<=', $launch->flagship_end);
                });

            })
            ->get();

        $dailyData = $allDeals->groupBy(function($deal) {
            $date = $deal->gc_created_at ?? $deal->created_at;
            return $date ? Carbon::parse($date)->format('Y-m-d') : 'unknown';
        })
            ->map(fn($dayDeals) => [
                'created' => $dayDeals->count(),
                'paid' => $dayDeals->where('payed_money', '>', 0)->count()
            ])
            ->sortBy(fn($val, $key) => $key);

        return [
            'datasets' => [
                [
                    'label' => 'Счетов выписано (шт)',
                    'data' => $dailyData->pluck('created')->values()->toArray(),
                    'backgroundColor' => 'rgba(107, 114, 128, 0.2)',
                    'borderColor' => '#6b7280',
                    'borderWidth' => 1
                ],
                [
                    'label' => 'Успешно оплачено (шт)',
                    'data' => $dailyData->pluck('paid')->values()->toArray(),
                    'backgroundColor' => 'rgba(34, 197, 94, 0.6)',
                    'borderColor' => '#22c55e',
                    'borderWidth' => 1
                ],
            ],
            'labels' => $dailyData->keys()->map(fn($date) => $date !== 'unknown' ? Carbon::parse($date)->format('d.m') : 'Нет даты')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}