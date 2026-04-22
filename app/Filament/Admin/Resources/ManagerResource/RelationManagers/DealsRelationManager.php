<?php

namespace App\Filament\Admin\Resources\ManagerResource\RelationManagers;

use App\Models\Deal;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DealsRelationManager extends RelationManager
{
    protected static string $relationship = 'deals';
    protected static ?string $title = 'Сделки менеджера';
    protected static ?string $recordTitleAttribute = 'gc_number';

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
            ->columns([
                Tables\Columns\TextColumn::make('gc_number')
                    ->label('Номер ГК')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn (string $state): string => match (mb_strtolower($state)) {
                        'new', 'новый' => 'gray',
                        'payed', 'завершен', 'оплачен' => 'success',
                        'part_payed', 'частично оплачен', 'в работе' => 'warning',
                        'cancelled', 'отменен', 'ложный' => 'danger',
                        default => 'primary',
                    })
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('cost')
                    ->label('Стоимость (₽)')
                    ->numeric(decimalPlaces: 0, decimalSeparator: ',', thousandsSeparator: ' ')
                    ->sortable(),

                Tables\Columns\TextColumn::make('payed_money')
                    ->label('Оплачено (₽)')
                    ->numeric(decimalPlaces: 0, decimalSeparator: ',', thousandsSeparator: ' ')
                    ->color(function ($record) {
                        if ($record->payed_money >= $record->cost && $record->cost > 0) return 'success';
                        if ($record->payed_money > 0) return 'warning';
                        return 'gray';
                    })
                    ->weight('bold')
                    ->sortable(),

                Tables\Columns\TextColumn::make('gc_created_at')
                    ->label('Дата создания')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('gc_created_at', 'desc')
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->actions([
                // НОВАЯ КНОПКА: Открыть в ГетКурсе
                Tables\Actions\Action::make('open_in_gc')
                    ->label('В ГетКурс')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->color('info')
                    ->url(function (Deal $record): ?string {
                        // Если нет ID заказа или не привязана школа, кнопку не нажимаем
                        if (!$record->gc_order_id || !$record->school?->getcourse_domain) {
                            return null;
                        }

                        // Очищаем домен от http/https и слэшей на всякий случай
                        $domain = preg_replace('#^https?://#', '', $record->school->getcourse_domain);
                        $domain = rtrim($domain, '/');

                        return "https://{$domain}/sales/control/deal/update/id/{$record->gc_order_id}";
                    })
                    ->openUrlInNewTab()
                    ->visible(fn (Deal $record): bool => !empty($record->gc_order_id)), // Показываем кнопку только если есть ID заказа
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}