<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Deal;
use App\Models\Launch;
use Livewire\Attributes\On;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;

class UtmDealChart extends ChartWidget
{
    protected static ?string $heading = 'Last-Touch: Источники оплат (по сделке)';
    protected int | string | array $columnSpan = 1; // Занимает вторую половину ширины
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

        if (!$launch || !$schoolId) return ['datasets' => [], 'labels' => []];

        // Получаем все оплаченные сделки (запрос идентичен первому графику)
        $deals = Deal::where('school_id', $schoolId)
            ->where('payed_money', '>', 0)
            ->where(function (Builder $q) use ($launch) {
                // Регистрации
                $q->orWhere(function (Builder $subQ) use ($launch) {
                    $subQ->whereHas('products', fn($p) => $p->whereIn('category', ['Регистрация', 'Регистрация на вебинар', 'Лид-магнит'])->whereHas('launches', fn($l) => $l->where('launches.id', $launch->id)));
                });
                // Трипваеры
                $q->orWhere(function (Builder $subQ) use ($launch) {
                    $subQ->whereHas('products', fn($p) => $p->where('category', 'Трипваер')->whereHas('launches', fn($l) => $l->where('launches.id', $launch->id)));
                    if ($launch->tripwire_start) $subQ->where('gc_created_at', '>=', $launch->tripwire_start);
                    if ($launch->tripwire_end) $subQ->where('gc_created_at', '<=', $launch->tripwire_end);
                });
                // Бронирования
                $q->orWhere(function (Builder $subQ) use ($launch) {
                    $subQ->whereHas('products', fn($p) => $p->where('category', 'Бронирование')->whereHas('launches', fn($l) => $l->where('launches.id', $launch->id)));
                    if ($launch->booking_start) $subQ->where('gc_created_at', '>=', $launch->booking_start);
                    if ($launch->booking_end) $subQ->where('gc_created_at', '<=', $launch->booking_end);
                });
                // Флагманы и Клубы
                $q->orWhere(function (Builder $subQ) use ($launch) {
                    $subQ->whereHas('products', fn($p) => $p->whereNotIn('category', ['Регистрация', 'Регистрация на вебинар', 'Лид-магнит', 'Трипваер', 'Бронирование'])->whereHas('launches', fn($l) => $l->where('launches.id', $launch->id)));
                    if ($launch->flagship_start) $subQ->where('gc_created_at', '>=', $launch->flagship_start);
                    if ($launch->flagship_end) $subQ->where('gc_created_at', '<=', $launch->flagship_end);
                });
            })->get();

        // Группируем по UTM самой сделки (Last-Touch)
        $grouped = $deals->groupBy(function($deal) {
            return $deal->utm_source ?: 'Прямой заход / Органика';
        })->map(fn($group) => $group->sum('payed_money'))->sortByDesc(fn($val) => $val);

        return [
            'datasets' => [
                [
                    'label' => 'Выручка (₽)',
                    'data' => $grouped->values()->toArray(),
                    // Чуть другая палитра, чтобы визуально отличать графики
                    'backgroundColor' => ['#14b8a6', '#f43f5e', '#8b5cf6', '#eab308', '#3b82f6', '#64748b', '#d946ef'],
                    'borderWidth' => 0,
                ],
            ],
            'labels' => $grouped->keys()->toArray(),
        ];
    }

    protected function getType(): string { return 'doughnut'; }
}