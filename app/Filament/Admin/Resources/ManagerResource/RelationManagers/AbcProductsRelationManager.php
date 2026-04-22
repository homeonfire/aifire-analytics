<?php

namespace App\Filament\Admin\Resources\ManagerResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Product;

class AbcProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'abcProducts';

    // НАЗВАНИЕ ВКЛАДКИ
    protected static ?string $title = 'ABC-анализ продуктов';

    // Иконка для вкладки
    protected static ?string $icon = 'heroicon-o-chart-pie';

    public function table(Table $table): Table
    {
        $ownerId = $this->getOwnerRecord()->id;

        return $table
            ->query(
            // ПЕРЕОПРЕДЕЛЯЕМ ЗАПРОС: берем продукты ТОЛЬКО из сделок этого менеджера
                Product::query()
                    ->whereHas('deals', function (Builder $query) use ($ownerId) {
                        $query->where('manager_id', $ownerId);
                    })
                    ->withCount([
                        'deals as total_deals' => fn(Builder $q) => $q->where('manager_id', $ownerId),
                        'deals as paid_deals' => fn(Builder $q) => $q->where('manager_id', $ownerId)->where('payed_money', '>', 0),
                    ])
                    ->withSum([
                        'deals as gross_revenue' => fn(Builder $q) => $q->where('manager_id', $ownerId),
                    ], 'payed_money')
                    ->withSum([
                        'deals as net_revenue' => fn(Builder $q) => $q->where('manager_id', $ownerId),
                    ], 'earned_value')
            )
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Название продукта / тарифа')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('category')
                    ->label('Категория')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_deals')
                    ->label('Заявок (шт)')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('paid_deals')
                    ->label('Оплат (шт)')
                    ->color('success')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('conversion')
                    ->label('Конверсия')
                    ->state(function ($record) {
                        if (!$record->total_deals) return '0%';
                        return round(($record->paid_deals / $record->total_deals) * 100, 1) . '%';
                    })
                    ->badge()
                    ->color(fn ($state) => (float)$state >= 30 ? 'success' : ((float)$state >= 10 ? 'warning' : 'danger'))
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('gross_revenue')
                    ->label('Выручка (Грязными)')
                    ->numeric(decimalPlaces: 0, decimalSeparator: ',', thousandsSeparator: ' ')
                    ->color('gray')
                    ->sortable()
                    ->alignRight(),

                Tables\Columns\TextColumn::make('net_revenue')
                    ->label('Чистая прибыль (₽)')
                    ->numeric(decimalPlaces: 0, decimalSeparator: ',', thousandsSeparator: ' ')
                    ->color('success')
                    ->weight('bold')
                    ->sortable()
                    ->alignRight(),
            ])
            ->groups([
                Tables\Grouping\Group::make('category')
                    ->label('Категория')
                    ->collapsible(),
            ])
            ->defaultGroup('category')
            ->defaultSort('net_revenue', 'desc');
    }
}