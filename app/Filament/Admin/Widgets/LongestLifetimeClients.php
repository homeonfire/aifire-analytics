<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\UnifiedClient;

class LongestLifetimeClients extends BaseWidget
{
    protected static bool $isDiscovered = false;
    protected static ?int $sort = 4; // Будет выводиться после графиков
    protected int | string | array $columnSpan = 'full'; // На всю ширину
    protected static ?string $heading = '🏆 Топ-5 самых преданных клиентов (Долгожители)';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                UnifiedClient::query()
                    ->select('unified_clients.*')
                    // Высчитываем первую оплату
                    ->selectRaw('(SELECT MIN(gc_paid_at) FROM deals WHERE deals.unified_client_id = unified_clients.id AND gc_paid_at IS NOT NULL) as first_payment')
                    // Высчитываем последнюю оплату
                    ->selectRaw('(SELECT MAX(gc_paid_at) FROM deals WHERE deals.unified_client_id = unified_clients.id AND gc_paid_at IS NOT NULL) as last_payment')
                    // Считаем разницу в днях
                    ->selectRaw('(SELECT DATEDIFF(MAX(gc_paid_at), MIN(gc_paid_at)) FROM deals WHERE deals.unified_client_id = unified_clients.id AND gc_paid_at IS NOT NULL) as lifetime_span')
                    // Отсекаем тех, кто купил только 1 раз (у них разница будет 0)
                    ->whereRaw('(SELECT DATEDIFF(MAX(gc_paid_at), MIN(gc_paid_at)) FROM deals WHERE deals.unified_client_id = unified_clients.id AND gc_paid_at IS NOT NULL) > 0')
                    // Сортируем по максимальному времени жизни и берем 5 лучших
                    ->orderByDesc('lifetime_span')
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('email')
                    ->label('VIP Клиент')
                    ->icon('heroicon-m-star')
                    ->color('warning')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('first_payment')
                    ->label('Первая оплата')
                    ->dateTime('d.m.Y')
                    ->color('gray'),

                Tables\Columns\TextColumn::make('last_payment')
                    ->label('Крайняя оплата')
                    ->dateTime('d.m.Y')
                    ->color('success'),

                // Красиво выводим количество дней со склонением
                Tables\Columns\TextColumn::make('lifetime_span')
                    ->label('Время с нами')
                    ->badge()
                    ->color('primary')
                    ->formatStateUsing(function (string $state): string {
                        $days = (int) $state;

                        $lastDigit = $days % 10;
                        $lastTwoDigits = $days % 100;

                        if ($lastTwoDigits >= 11 && $lastTwoDigits <= 19) {
                            $word = 'дней';
                        } elseif ($lastDigit === 1) {
                            $word = 'день';
                        } elseif ($lastDigit >= 2 && $lastDigit <= 4) {
                            $word = 'дня';
                        } else {
                            $word = 'дней';
                        }

                        return $days . ' ' . $word;
                    }),
                Tables\Columns\TextColumn::make('deals_sum_payed_money')
                    ->label('Принес денег (LTV)')
                    ->money('RUB')
                    ->color('success')
                    ->weight('bold')
                    // Считаем сумму всех оплат на лету прямо из таблицы заказов
                    ->state(function ($record) {
                        return $record->deals()->sum('payed_money');
                    }),
            ])
            ->paginated(false); // Убираем пагинацию
    }
}