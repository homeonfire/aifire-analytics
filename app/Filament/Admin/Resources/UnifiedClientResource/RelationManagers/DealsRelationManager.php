<?php

namespace App\Filament\Admin\Resources\UnifiedClientResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DealsRelationManager extends RelationManager
{
    protected static string $relationship = 'deals';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('gc_number')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('gc_number')
            ->modifyQueryUsing(fn ($query) => $query->with('products')) // Подгружаем продукты
            ->columns([
                Tables\Columns\TextColumn::make('gc_number')->label('№ Заказа')->searchable(),

                Tables\Columns\TextColumn::make('products.title')
                    ->label('Что купил / получил')
                    ->badge()
                    ->color('success')
                    ->separator(',')
                    ->wrap(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Завершен' => 'success',
                        'В работе', 'Частично оплачен' => 'warning',
                        'Отменен', 'Ложный' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('cost')->label('Чек')->money('RUB'),
                Tables\Columns\TextColumn::make('payed_money')->label('Оплатил')->money('RUB')->weight('bold'),
                Tables\Columns\TextColumn::make('gc_created_at')->label('Дата')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->defaultSort('gc_created_at', 'desc'); // Сортируем от новых к старым
    }
}
