<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\GcExportResource\Pages;
use App\Models\GcExport;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class GcExportResource extends Resource
{
    protected static ?string $model = GcExport::class;

    // ВАЖНО: Привязка к школе, чтобы не было ошибки мультитенантности!
    protected static ?string $tenantOwnershipRelationshipName = 'school';

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path-rounded-square';
    protected static ?string $navigationLabel = 'Очередь импорта (ГК)';
    protected static ?string $modelLabel = 'Выгрузку';
    protected static ?string $pluralModelLabel = 'Очередь импорта';
    protected static ?string $navigationGroup = 'Настройки';

    public static function form(Form $form): Form
    {
        // Оставляем форму пустой, так как мы запретим ручное создание и редактирование
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            // МАГИЯ FILAMENT: Таблица сама обновляет данные каждые 10 секунд!
            ->poll('10s')
            ->columns([
                Tables\Columns\TextColumn::make('export_id')
                    ->label('ID Задачи (ГК)')
                    ->searchable()
                    ->copyable() // Можно скопировать ID по клику
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('date_from')
                    ->label('Период С')
                    ->date('d.m.Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('date_to')
                    ->label('Период По')
                    ->date('d.m.Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'completed' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'В процессе (ждем ГК)',
                        'completed' => 'Завершено',
                        'failed' => 'Ошибка',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('error_message')
                    ->label('Текст ошибки')
                    ->color('danger')
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true), // Прячем колонку по умолчанию

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Запущено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc') // Новые задачи всегда сверху
            ->actions([
                Tables\Actions\DeleteAction::make(), // Можно удалить зависшую задачу
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageGcExports::route('/'),
        ];
    }

    // Запрещаем менеджерам руками создавать записи (это делает только крон)
    public static function canCreate(): bool
    {
        return false;
    }

    // Запрещаем редактирование
    public static function canEdit($record): bool
    {
        return false;
    }
}