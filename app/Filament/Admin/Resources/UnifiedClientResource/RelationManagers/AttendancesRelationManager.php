<?php

namespace App\Filament\Resources\UnifiedClientResource\RelationManagers; // <-- Здесь убрали Admin\

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class AttendancesRelationManager extends RelationManager
{
    protected static string $relationship = 'attendances';
    protected static ?string $title = 'Посещения вебинаров';
    protected static ?string $icon = 'heroicon-o-video-camera';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('webinar.title')
                    ->label('Вебинар')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('webinar.started_at')
                    ->label('Дата эфира')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_minutes')
                    ->label('Время присутствия')
                    ->suffix(' мин.')
                    ->badge()
                    ->color(fn ($state) => $state >= 60 ? 'success' : ($state >= 15 ? 'info' : 'warning'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('device')
                    ->label('Устройство')
                    ->badge()
                    ->color('gray'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('view_webinar')
                    ->label('Открыть вебинар')
                    ->icon('heroicon-m-eye')
                    ->url(fn ($record) => \App\Filament\Admin\Resources\WebinarResource::getUrl('view', ['record' => $record->webinar_id]))
            ])
            ->bulkActions([
                //
            ])
            ->defaultSort('created_at', 'desc');
    }
}