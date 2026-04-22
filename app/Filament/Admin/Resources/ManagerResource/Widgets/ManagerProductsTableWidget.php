<?php

namespace App\Filament\Admin\Resources\ManagerResource\Widgets;

use App\Models\Product;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ManagerProductsTableWidget extends BaseWidget
{
    // Сюда Filament автоматически передаст текущего менеджера
    public ?Model $record = null;

    // Заголовок виджета (как на твоем скрине)
    protected static ?string $heading = 'Срез по тарифам и продуктам (ABC-анализ)';

    public function table(Table $table): Table
    {
        return $table
            ->query(
            // Выбираем только те продукты, которые есть в сделках ЭТОГО менеджера
                Product::query()
                    ->whereHas('deals', function (Builder $query) {
                        $query->where('manager_id', $this->record->id);
                    })
                    // Считаем заявки и оплаты именно этого менеджера
                    ->withCount([
                        'deals as total_deals' => fn(Builder $q) => $q->where('manager_id', $this->record->id),
                        'deals as paid_deals' => fn(Builder $q) => $q->where('manager_id', $this->record->id)->where('payed_money', '>', 0),
                    ])
                    // Считаем грязную и чистую выручку
                    ->withSum([
                        'deals as gross_revenue' => fn(Builder $q) => $q->where('manager_id', $this->record->id),
                    ], 'payed_money')
                    ->withSum([
                        'deals as net_revenue' => fn(Builder $q) => $q->where('manager_id', $this->record->id),
                    ], 'earned_value')
            )
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
                    // Подсвечиваем цветом хорошую и плохую конверсию
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
                // ГРУППИРОВКА ПО КАТЕГОРИЯМ
                Tables\Grouping\Group::make('category')
                    ->label('Категория')
                    ->collapsible(),
            ])
            ->defaultGroup('category') // Сразу включаем группировку по умолчанию
            ->defaultSort('net_revenue', 'desc');
    }
}