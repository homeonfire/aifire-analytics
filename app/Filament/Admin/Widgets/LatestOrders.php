<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\Deal;

class LatestOrders extends BaseWidget
{
    protected static bool $isDiscovered = false;
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full'; // Растягиваем на всю ширину
    protected static ?string $heading = 'Свежие успешные оплаты';

    public function table(Table $table): Table
    {
        return $table
            ->query(
            // Берем только те заказы, где были реальные деньги, сортируем от новых к старым, берем 5 штук
                Deal::query()
                    ->with(['client', 'products'])
                    ->where('payed_money', '>', 0)
                    ->latest('gc_created_at')
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('client.email')
                    ->label('Клиент')
                    ->icon('heroicon-m-user')
                    ->color('primary'),

                Tables\Columns\TextColumn::make('products.title')
                    ->label('Что купили')
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('payed_money')
                    ->label('Сумма')
                    ->money('RUB')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('gc_created_at')
                    ->label('Когда')
                    ->dateTime('d.m.Y H:i')
                    ->description(fn ($record) => $record->gc_created_at?->diffForHumans()), // Выведет "2 часа назад"
            ])
            ->paginated(false); // Прячем пагинацию, нам тут нужен только топ
    }
}