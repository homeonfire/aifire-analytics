<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UnifiedClientResource\Pages;
use App\Filament\Resources\UnifiedClientResource\RelationManagers;
use App\Models\UnifiedClient;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use App\Filament\Resources\UnifiedClientResource\RelationManagers\DealsRelationManager;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Split;

class UnifiedClientResource extends Resource
{
    protected static ?string $model = UnifiedClient::class;

    // Иконка в боковом меню (группа людей)
    protected static ?string $navigationIcon = 'heroicon-o-users';

    // Название пункта меню
    protected static ?string $navigationLabel = 'Клиенты';

    // Название в хлебных крошках и заголовках
    protected static ?string $modelLabel = 'Клиент';
    protected static ?string $pluralModelLabel = 'Клиенты';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('avatar_url')
                    ->label('Аватар')
                    ->circular(), // Круглая аватарка
                TextColumn::make('first_name')
                    ->label('Имя')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('last_name')
                    ->label('Фамилия')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->copyable() // Можно скопировать по клику
                    ->searchable(),
                TextColumn::make('phone')
                    ->label('Телефон')
                    ->searchable(),
                TextColumn::make('total_spent')
                    ->label('LTV (Выручка)')
                    ->money('RUB') // Автоматически форматирует как деньги
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Добавлен')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('webinars')
                    ->relationship('webinars', 'title')
                    ->label('Был на вебинаре')
                    ->searchable()
                    ->preload()
                    ->indicator('Вебинар'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(), // Кнопка "Просмотр"
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Grid::make(3)
                    ->schema([
                        // ЛЕВАЯ КОЛОНКА - Профиль (1/3 экрана)
                        Grid::make(1)->schema([
                            Section::make('Профиль')
                                ->icon('heroicon-o-user')
                                ->schema([
                                    ImageEntry::make('avatar_url')
                                        ->hiddenLabel()
                                        ->circular()
                                        ->defaultImageUrl('https://ui-avatars.com/api/?background=0D8ABC&color=fff&name=User'),

                                    TextEntry::make('first_name')->label('Имя')->placeholder('Не указано'),
                                    TextEntry::make('last_name')->label('Фамилия')->placeholder('Не указано'),
                                    TextEntry::make('email')->icon('heroicon-m-envelope')->copyable(),
                                    TextEntry::make('phone')->label('Телефон')->icon('heroicon-m-phone')->copyable()->placeholder('Нет номера'),
                                    TextEntry::make('city')->label('Город')->icon('heroicon-m-map-pin')->placeholder('Неизвестен'),
                                ]),

                            Section::make('Откуда пришел (UTM)')
                                ->icon('heroicon-o-megaphone')
                                ->schema([
                                    TextEntry::make('utm_source')->label('Источник')->badge()->color('info')->placeholder('Органика / Прямой заход'),
                                    TextEntry::make('utm_medium')->label('Канал')->placeholder('-'),
                                    TextEntry::make('utm_campaign')->label('Кампания')->placeholder('-'),
                                ])->collapsed(), // Свернуто по умолчанию, чтобы не занимать место
                        ])->columnSpan(1),

                        // ПРАВАЯ КОЛОНКА - Сводка и показатели (2/3 экрана)
                        Grid::make(1)->schema([
                            Section::make('Ключевые показатели (Сводка)')
                                ->schema([
                                    Grid::make(3)->schema([
                                        TextEntry::make('total_spent')
                                            ->label('Принес денег (LTV)')
                                            ->money('RUB')
                                            ->color('success')
                                            ->size(TextEntry\TextEntrySize::Large)
                                            ->weight('bold'),

                                        TextEntry::make('lifetime_days') // Название может быть любым, так как мы сами собираем данные
                                        ->label('Жизненный цикл (с 1-го заказа)')
                                            ->state(function ($record) {
                                                // Берем дату самого первого заказа из Геткурса
                                                $firstDealDate = $record->deals()->min('gc_created_at');

                                                // Если заказов еще нет (например, зашел с Тильды), считаем от создания профиля
                                                if (!$firstDealDate) {
                                                    $firstDealDate = $record->created_at;
                                                }

                                                if (!$firstDealDate) return 'Неизвестно';

                                                $date = \Carbon\Carbon::parse($firstDealDate);
                                                $days = (int) $date->diffInDays(now());

                                                // Надежное склонение слова "день"
                                                $lastDigit = $days % 10;
                                                $lastTwoDigits = $days % 100;

                                                if ($lastTwoDigits >= 11 && $lastTwoDigits <= 19) {
                                                    $word = 'дней';
                                                } elseif ($lastDigit === 1) {
                                                    $word = 'день';
                                                } elseif ($lastDigit >= 2 && $lastDigit <= 4) {
                                                    $word = 'дня';
                                                } else {
                                                    $word = 'дней';
                                                }

                                                // Если заказ был только сегодня, выведем "1 день" (чтобы не писать 0 дней)
                                                if ($days === 0) return 'Новый клиент';

                                                return $days . ' ' . $word;
                                            })
                                            ->color('primary')
                                            ->size(TextEntry\TextEntrySize::Large)
                                            ->weight('bold'),

                                        TextEntry::make('deals_count')
                                            ->label('Всего заказов')
                                            ->state(fn ($record) => $record->deals()->count() . ' шт.')
                                            ->size(TextEntry\TextEntrySize::Large)
                                            ->weight('bold'),
                                    ]),
                                ]),

                            // Здесь можно добавить блок с текстовым примечанием менеджера
                            Section::make('ID во внешних системах')
                                ->icon('heroicon-o-link')
                                ->schema([
                                    TextEntry::make('getcourse_id')->label('GetCourse ID')->copyable()->placeholder('Нет'),
                                    TextEntry::make('salebot_id')->label('SaleBot ID')->copyable()->placeholder('Нет'),
                                ])->columns(2),

                        ])->columnSpan(2),
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\DealsRelationManager::class, // <-- Вот эта строчка
            RelationManagers\AttendancesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUnifiedClients::route('/'),
            'create' => Pages\CreateUnifiedClient::route('/create'),
            'view' => Pages\ViewUnifiedClient::route('/{record}'),
            'edit' => Pages\EditUnifiedClient::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return true;
    }
}
