<?php

namespace App\Filament\Admin\Resources\ManagerResource\Widgets;

use App\Models\Deal;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Facades\Filament;

class ManagerStatsOverview extends BaseWidget
{
    // Указываем Filament, что мы хотим сетку из 4 колонок (чтобы карточки встали в один ряд)
    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $schoolId = Filament::getTenant()?->id;

        // Базовый запрос: только активные менеджеры
        $query = Deal::where('school_id', $schoolId)
            ->whereHas('manager', function ($q) {
                $q->where('is_active', true);
            });

        $totalDeals = (clone $query)->count();
        $fullPaid = (clone $query)->whereRaw('payed_money >= cost')->where('payed_money', '>', 0)->count();
        $partialPaid = (clone $query)->whereRaw('payed_money < cost')->where('payed_money', '>', 0)->count();
        $inProgress = (clone $query)->where('payed_money', 0)->count();

        $grossRevenue = (clone $query)->sum('payed_money');
        $netRevenue = (clone $query)->sum('earned_value');

        $paidDeals = $fullPaid + $partialPaid;
        $conversion = $totalDeals > 0 ? round(($paidDeals / $totalDeals) * 100, 1) : 0;

        // --- НОВЫЙ БЛОК: СРЕДНИЙ ЧЕК ---
        // Берем среднее значение только по тем сделкам, в которых есть продукты,
        // не относящиеся к регистрациям и броням.
        $avgCheck = (clone $query)
            ->whereHas('products', function ($q) {
                $q->whereNotIn('category', ['Регистрация', 'Бронирование'])
                    ->orWhereNull('category'); // Оставляем null на случай, если тариф еще не размечен в админке
            })
            ->where('payed_money', '>', 0)
            ->avg('payed_money') ?? 0;

        return [
            Stat::make('Чистая прибыль ОП', number_format($netRevenue, 0, ',', ' ') . ' ₽')
                ->description('Грязными: ' . number_format($grossRevenue, 0, ',', ' ') . ' ₽')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Средний чек (Флагманы)', number_format($avgCheck, 0, ',', ' ') . ' ₽')
                ->description('Исключая регистрации')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('primary'),

            Stat::make('Воронка заявок', "{$totalDeals} шт.")
                ->description("Оплат: {$paidDeals} | Частичных: {$partialPaid} | В работе: {$inProgress}")
                ->descriptionIcon('heroicon-m-inbox-stack')
                ->color('info'),

            Stat::make('Конверсия в оплату', "{$conversion}%")
                ->description('Из заявки в любую оплату')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($conversion >= 30 ? 'success' : 'warning'),
        ];
    }
}