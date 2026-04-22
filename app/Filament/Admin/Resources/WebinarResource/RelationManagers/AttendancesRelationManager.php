<?php

namespace App\Filament\Admin\Resources\WebinarResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class AttendancesRelationManager extends RelationManager
{
    protected static string $relationship = 'attendances';
    protected static ?string $title = 'Участники вебинара';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('client.first_name')->label('Имя')->searchable(),
                Tables\Columns\TextColumn::make('client.email')->label('Email')->searchable(),
                Tables\Columns\TextColumn::make('client.phone')->label('Телефон'),
                Tables\Columns\TextColumn::make('city')->label('Город')->sortable(),
                Tables\Columns\TextColumn::make('total_minutes')
                    ->label('Мин. в эфире')
                    ->suffix(' мин.')
                    ->sortable()
                    ->summarize(Tables\Columns\Summarizers\Average::make()->label('Среднее время')),
            ])
            ->filters([])
            ->headerActions([])
            ->actions([
                Tables\Actions\ViewAction::make(), // Кнопка просмотра
            ])
            ->bulkActions([]);
    }

    // ДОБАВЛЯЕМ ЭТОТ МЕТОД ДЛЯ КАРТОЧКИ ПРОСМОТРА
    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Профиль участника')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Infolists\Components\TextEntry::make('client.first_name')->label('Имя')->weight('bold'),
                        Infolists\Components\TextEntry::make('client.email')->label('Email')->icon('heroicon-m-envelope')->copyable(),
                        Infolists\Components\TextEntry::make('client.phone')->label('Телефон')->icon('heroicon-m-phone')->copyable(),
                        Infolists\Components\TextEntry::make('city')->label('Город')->placeholder('Не указан'),
                        Infolists\Components\TextEntry::make('device')->label('Устройство')->badge()->color('info'),
                    ])->columns(3),

                Infolists\Components\Section::make('Статистика присутствия')
                    ->icon('heroicon-o-clock')
                    ->schema([
                        Infolists\Components\TextEntry::make('total_minutes')
                            ->label('Общее время в эфире')
                            ->suffix(' мин.')
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                            ->weight('bold')
                            ->color('success'),

                        // Выводим те самые интервалы из базы
                        Infolists\Components\RepeatableEntry::make('intervals')
                            ->label('Детализация (входы и выходы)')
                            ->schema([
                                Infolists\Components\TextEntry::make('entered_at')->label('Зашел'),
                                Infolists\Components\TextEntry::make('left_at')->label('Вышел'),
                                Infolists\Components\TextEntry::make('minutes')->label('Длительность')->suffix(' мин.')->badge(),
                            ])->columns(3)
                    ]),

                // Секция для чата (пока будет пустой, пока не выполним Шаг 2)
                Infolists\Components\Section::make('Сообщения в чате')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('chatMessages')
                            ->label('')
                            ->schema([
                                Infolists\Components\TextEntry::make('time')->label('Время')->weight('bold'),
                                Infolists\Components\TextEntry::make('message')->label('Сообщение')->columnSpan(2),
                            ])->columns(3)
                    ])
            ]);
    }
}