<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Deal;
use App\Models\Launch;
use Livewire\Attributes\On;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;

class LaunchKPIs extends BaseWidget
{
    protected static bool $isDiscovered = false;
    protected static bool $isLazy = false;

    // Заставляем виджет выстроиться в 4 колонки (в 1 ряд)
    protected function getColumns(): int
    {
        return 4;
    }

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

    protected function getStats(): array
    {
        $schoolId = Filament::getTenant()?->id;
        $launch = Launch::find($this->launchId);

        if (!$launch || !$schoolId) return [];

        // 1. ЕДИНЫЙ ЗАПРОС: Получаем ВСЕ сделки этого запуска, учитывая окна дат
        $launchDeals = Deal::with('products')
            ->where('school_id', $schoolId)
            ->where(function (Builder $q) use ($launch) {

                // РЕГИСТРАЦИИ (Берем все за привязанный запуск без жестких окон дат)
                $q->orWhere(function (Builder $subQ) use ($launch) {
                    $subQ->whereHas('products', function(Builder $p) use ($launch) {
                        $p->whereIn('category', ['Регистрация', 'Регистрация на вебинар', 'Лид-магнит'])
                            ->whereHas('launches', fn($l) => $l->where('launches.id', $launch->id));
                    });
                });

                // ТРИПВАЕРЫ
                $q->orWhere(function (Builder $subQ) use ($launch) {
                    $subQ->whereHas('products', function(Builder $p) use ($launch) {
                        $p->where('category', 'Трипваер')
                            ->whereHas('launches', fn($l) => $l->where('launches.id', $launch->id));
                    });
                    if ($launch->tripwire_start) $subQ->where('gc_created_at', '>=', $launch->tripwire_start);
                    if ($launch->tripwire_end) $subQ->where('gc_created_at', '<=', $launch->tripwire_end);
                });

                // БРОНИРОВАНИЯ
                $q->orWhere(function (Builder $subQ) use ($launch) {
                    $subQ->whereHas('products', function(Builder $p) use ($launch) {
                        $p->where('category', 'Бронирование')
                            ->whereHas('launches', fn($l) => $l->where('launches.id', $launch->id));
                    });
                    if ($launch->booking_start) $subQ->where('gc_created_at', '>=', $launch->booking_start);
                    if ($launch->booking_end) $subQ->where('gc_created_at', '<=', $launch->booking_end);
                });

                // ФЛАГМАНЫ И ПРОЧАЯ КОММЕРЦИЯ
                $q->orWhere(function (Builder $subQ) use ($launch) {
                    $subQ->whereHas('products', function(Builder $p) use ($launch) {
                        $p->whereNotIn('category', ['Регистрация', 'Регистрация на вебинар', 'Лид-магнит', 'Трипваер', 'Бронирование'])
                            ->whereHas('launches', fn($l) => $l->where('launches.id', $launch->id));
                    });
                    if ($launch->flagship_start) $subQ->where('gc_created_at', '>=', $launch->flagship_start);
                    if ($launch->flagship_end) $subQ->where('gc_created_at', '<=', $launch->flagship_end);
                });

            })
            ->get(); // Выгружаем коллекцию в память для быстрых расчетов

        // 2. Разделяем сделки на Регистрации и Коммерцию
        $registrationsDeals = $launchDeals->filter(function($deal) {
            return $deal->products->contains(fn($p) => in_array($p->category, ['Регистрация', 'Регистрация на вебинар', 'Лид-магнит']));
        });

        $salesDeals = $launchDeals->reject(function($deal) {
            return $deal->products->contains(fn($p) => in_array($p->category, ['Регистрация', 'Регистрация на вебинар', 'Лид-магнит']));
        });

        // 3. Считаем деньги (ГРЯЗНАЯ И ЧИСТАЯ)
        // Регистрации
        $registrationsRevenue = $registrationsDeals->sum('payed_money');
        $netRegistrationsRevenue = $registrationsDeals->sum('earned_value');

        // Коммерция
        $paidCommercialDeals = $salesDeals->filter(fn($deal) => $deal->payed_money > 0);
        $commercialRevenue = $paidCommercialDeals->sum('payed_money');
        $netCommercialRevenue = $paidCommercialDeals->sum('earned_value');

        // Отдел продаж
        $managerDeals = $paidCommercialDeals->filter(fn($deal) => !empty($deal->manager_id));
        $managerRevenue = $managerDeals->sum('payed_money');
        $netManagerRevenue = $managerDeals->sum('earned_value');

        // Итоги
        $actualRevenue = $registrationsRevenue + $commercialRevenue;
        $netActualRevenue = $netRegistrationsRevenue + $netCommercialRevenue;

        $expectedRevenue = $registrationsDeals->sum('cost') + $salesDeals->sum('cost');

        return [
            Stat::make('Общая прибыль', number_format($netActualRevenue, 0, ',', ' ') . ' ₽')
                ->description('Грязными: ' . number_format($actualRevenue, 0, ',', ' ') . ' ₽')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Коммерческая прибыль', number_format($netCommercialRevenue, 0, ',', ' ') . ' ₽')
                ->description('Грязными: ' . number_format($commercialRevenue, 0, ',', ' ') . ' ₽')
                ->color('success'),

            Stat::make('Прибыль ОП', number_format($netManagerRevenue, 0, ',', ' ') . ' ₽')
                ->description('Грязными: ' . number_format($managerRevenue, 0, ',', ' ') . ' ₽')
                ->color('primary'),

            Stat::make('Потенциал (Выписано)', number_format($expectedRevenue, 0, ',', ' ') . ' ₽')
                ->description('Сумма всех выставленных счетов')
                ->color('gray'),
        ];
    }
}