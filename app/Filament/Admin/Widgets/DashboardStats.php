<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\UnifiedClient;
use App\Models\Deal;
use Filament\Facades\Filament;

class MainDashboardStats extends BaseWidget // Название класса может отличаться, оставь свое, если оно другое!
{
    protected static ?int $sort = 1;

    // Скрываем плашки, если данных в этой школе еще нет
    public static function canView(): bool
    {
        $school = Filament::getTenant();
        if (!$school) return false;

        return UnifiedClient::where('school_id', $school->id)->exists()
            || Deal::where('school_id', $school->id)->exists();
    }

    protected function getStats(): array
    {
        $school = Filament::getTenant();
        if (!$school) return [];

        // Изолируем расчеты по текущей школе
        $revenue = Deal::where('school_id', $school->id)->sum('payed_money');
        $clients = UnifiedClient::where('school_id', $school->id)->count();
        $orders = Deal::where('school_id', $school->id)->count();

        return [
            Stat::make('Общая выручка', number_format($revenue, 0, '.', ' ') . ' ₽')
                ->description('Все фактические поступления')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Клиентов в базе', number_format($clients, 0, '.', ' '))
                ->description('Уникальные лиды и покупатели')
                ->descriptionIcon('heroicon-m-users')
                ->color('warning'),

            Stat::make('Всего заказов', number_format($orders, 0, '.', ' '))
                ->description('Включая неоплаченные')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('primary'),
        ];
    }
}