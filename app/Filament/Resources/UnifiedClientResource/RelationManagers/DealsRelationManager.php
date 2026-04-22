<?php

namespace App\Filament\Resources\UnifiedClientResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;

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
            ->heading('Заказы клиента')
            ->columns([
                TextColumn::make('gc_number')->label('№ Заказа'),
                TextColumn::make('product_title')->label('Продукт')->wrap(),
                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Завершен' => 'success',
                        'В работе' => 'warning',
                        'Отменен'  => 'danger',
                        default    => 'gray',
                    }),
                TextColumn::make('cost')->label('Сумма')->money('RUB'),
                TextColumn::make('gc_created_at')->label('Дата')->dateTime('d.m.Y'),
            ]);
    }
}
