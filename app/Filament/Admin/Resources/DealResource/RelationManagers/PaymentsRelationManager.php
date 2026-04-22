<?php

namespace App\Filament\Admin\Resources\DealResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    // Переводим заголовки
    protected static ?string $title = 'История платежей';
    protected static ?string $modelLabel = 'Платеж';
    protected static ?string $pluralModelLabel = 'Платежи';

    public function form(Form $form): Form
    {
        // Оставляем пустой, так как платежи мы только читаем из ГК, а не создаем руками
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('gc_payment_id')
            ->columns([
                Tables\Columns\TextColumn::make('gc_payment_id')
                    ->label('ID (ГК)')
                    ->searchable()
                    ->copyable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('gc_created_at')
                    ->label('Дата транзакции')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('payment_system')
                    ->label('Способ оплаты')
                    ->searchable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Сумма')
                    ->money('RUB')
                    ->sortable(),

                Tables\Columns\TextColumn::make('commission_amount')
                    ->label('Комиссия')
                    ->money('RUB')
                    ->color('danger')
                    ->toggleable(isToggledHiddenByDefault: true), // Скрыто по умолчанию, можно включить

                Tables\Columns\TextColumn::make('net_amount')
                    ->label('Чистыми')
                    ->money('RUB')
                    ->color('success')
                    ->weight('bold')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn (string $state): string => match (strtolower($state)) {
                        'получен', 'accepted', 'завершен', 'оплачен' => 'success',
                        'expected', 'ожидается' => 'warning',
                        'returned', 'возврат' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->defaultSort('gc_created_at', 'desc') // Самые свежие платежи сверху
            ->filters([
                //
            ])
            ->headerActions([
                // Убираем кнопку Create, так как данные идут из синхронизации
            ])
            ->actions([
                // Убираем Edit и Delete, чтобы менеджеры не могли случайно удалить реальный платеж
            ])
            ->bulkActions([
                // Пусто
            ]);
    }
}