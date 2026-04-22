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

class LaunchFunnelKPIs extends BaseWidget
{
    protected static bool $isDiscovered = false;
    protected static bool $isLazy = false;

    public ?int $launchId = null;

    // Задаем сетку из 3 колонок, чтобы 6 карточек встали ровно в 2 ряда
    protected function getColumns(): int
    {
        return 3;
    }

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

        // 1. Единый запрос всех сделок запуска по категориям и окнам дат
        $launchDeals = Deal::with('products')
            ->where('school_id', $schoolId)
            ->where(function (Builder $q) use ($launch) {

                // РЕГИСТРАЦИИ
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
            ->get();

        // 2. Разделяем на Регистрации и Коммерцию
        $registrationsDeals = $launchDeals->filter(function($deal) {
            return $deal->products->contains(fn($p) => in_array($p->category, ['Регистрация', 'Регистрация на вебинар', 'Лид-магнит']));
        });

        $registrationsCount = $registrationsDeals->count();
        $paidRegistrationsCount = $registrationsDeals->where('payed_money', '>', 0)->count();

        $salesDeals = $launchDeals->reject(function($deal) {
            return $deal->products->contains(fn($p) => in_array($p->category, ['Регистрация', 'Регистрация на вебинар', 'Лид-магнит']));
        });

        // 3. Считаем метрики воронки
        $commercialOrdersCount = $salesDeals->count();
        $paidCommercialDeals = $salesDeals->where('payed_money', '>', 0);
        $paidCommercialCount = $paidCommercialDeals->count();

        // --- ФИНАНСЫ: ГРЯЗНАЯ И ЧИСТАЯ ВЫРУЧКА ---
        $commercialRevenue = $paidCommercialDeals->sum('payed_money');
        $netCommercialRevenue = $paidCommercialDeals->sum('earned_value');

        // Конверсии
        $conversion = $commercialOrdersCount > 0 ? round(($paidCommercialCount / $commercialOrdersCount) * 100, 1) : 0;
        $aov = $paidCommercialCount > 0 ? round($commercialRevenue / $paidCommercialCount) : 0;

        $registrationDescription = $paidRegistrationsCount > 0
            ? "Из них платных: {$paidRegistrationsCount}"
            : "Все бесплатные";

        return [
            Stat::make('Регистраций', number_format($registrationsCount, 0, ',', ' '))
                ->description($registrationDescription)
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('info'),

            Stat::make('Коммерческих заявок', number_format($commercialOrdersCount, 0, ',', ' '))
                ->description('Без учета регистраций')
                ->descriptionIcon('heroicon-m-ticket')
                ->color('primary'),

            Stat::make('Успешных оплат', number_format($paidCommercialCount, 0, ',', ' '))
                ->description('Оплаченные коммерческие')
                ->color('success'),

            Stat::make('Конверсия в оплату', $conversion . '%')
                ->description('Из коммерч. заявки в оплату')
                ->color($conversion >= 30 ? 'success' : 'warning'),

            Stat::make('Средний чек (AOV)', number_format($aov, 0, ',', ' ') . ' ₽')
                ->description('По коммерческим продуктам')
                ->color('primary'),

            // --- НОВАЯ КАРТОЧКА С ЧИСТОЙ ПРИБЫЛЬЮ ---
            Stat::make('Чистая прибыль', number_format($netCommercialRevenue, 0, ',', ' ') . ' ₽')
                ->description('Грязными: ' . number_format($commercialRevenue, 0, ',', ' ') . ' ₽')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
        ];
    }
}