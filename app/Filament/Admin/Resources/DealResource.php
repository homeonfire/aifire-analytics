<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\DealResource\Pages;
use App\Models\Deal;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Filament\Resources\UnifiedClientResource;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;

class DealResource extends Resource
{
    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $navigationLabel = 'Все заказы';
    protected static ?string $modelLabel = 'Заказ';
    protected static ?string $pluralModelLabel = 'Заказы';
    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        return true;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(3)->schema([
                    // ОСНОВНАЯ ИНФОРМАЦИЯ
                    Forms\Components\Section::make('Информация о заказе')
                        ->icon('heroicon-o-information-circle')
                        ->schema([
                            Forms\Components\TextInput::make('gc_number')
                                ->label('Номер в GetCourse')
                                ->readOnly(),

                            Forms\Components\Select::make('unified_client_id')
                                ->label('Клиент')
                                ->relationship('client', 'email')
                                ->searchable()
                                ->required(),

                            Forms\Components\Select::make('manager_id')
                                ->label('Менеджер')
                                ->relationship('manager', 'name')
                                ->searchable(),

                            Forms\Components\TextInput::make('status')
                                ->label('Статус')
                                ->required(),

                            Forms\Components\DateTimePicker::make('gc_created_at')
                                ->label('Создан в ГК'),

                            Forms\Components\DateTimePicker::make('gc_paid_at')
                                ->label('Оплачен в ГК'),
                        ])->columnSpan(2)->columns(2),

                    // ФИНАНСЫ
                    Forms\Components\Section::make('Финансы')
                        ->icon('heroicon-o-banknotes')
                        ->schema([
                            Forms\Components\TextInput::make('cost')
                                ->label('Стоимость')
                                ->numeric()
                                ->suffix('₽'),

                            Forms\Components\TextInput::make('payed_money')
                                ->label('Оплачено')
                                ->numeric()
                                ->suffix('₽'),
                        ])->columnSpan(1),

                    // АНАЛИТИКА (LAST-TOUCH)
                    Forms\Components\Section::make('Аналитика заказа (Last-Touch)')
                        ->icon('heroicon-o-chart-bar')
                        ->schema([
                            Forms\Components\TextInput::make('promocode')
                                ->label('Промокод')
                                ->prefixIcon('heroicon-o-ticket'), // Исправили на prefixIcon

                            Forms\Components\TextInput::make('utm_source')->label('UTM Source'),
                            Forms\Components\TextInput::make('utm_medium')->label('UTM Medium'),
                            Forms\Components\TextInput::make('utm_campaign')->label('UTM Campaign'),
                            Forms\Components\TextInput::make('utm_term')->label('UTM Term'),
                            Forms\Components\TextInput::make('utm_content')->label('UTM Content'),
                        ])->columns(3)->columnSpan(3),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            // Подгружаем продукты заранее, чтобы база не тормозила
            ->modifyQueryUsing(fn ($query) => $query->with('products'))
            ->columns([
                TextColumn::make('gc_number')->label('№')->searchable()->sortable(),

                TextColumn::make('client.email')
                    ->label('Email клиента')
                    ->searchable()
                    ->url(fn ($record) => $record->client ? UnifiedClientResource::getUrl('view', ['record' => $record->client->id]) : null)
                    ->color('primary'),

                TextColumn::make('products.title')
                    ->label('Состав заказа')
                    ->badge()
                    ->color('info')
                    ->separator(', ')
                    ->searchable(),

                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Завершен', 'payed' => 'success',
                        'В работе', 'Частично оплачен', 'in_work', 'part_payed' => 'warning',
                        'Отменен', 'Ложный', 'cancelled', 'false' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('cost')->label('Стоимость')->money('RUB')->sortable(),
                TextColumn::make('payed_money')->label('Оплачено')->money('RUB')->sortable(),
                TextColumn::make('earned_value')
                    ->label('Получено (₽)')
                    ->numeric(decimalPlaces: 0, decimalSeparator: ',', thousandsSeparator: ' ')
                    ->color('success')
                    ->weight('bold')
                    ->sortable(),

                TextColumn::make('commission')
                    ->label('Комиссия')
                    ->state(function ($record) {
                        // Считаем комиссию только если есть и оплата, и чистая прибыль
                        if ($record->payed_money > 0 && $record->earned_value > 0) {
                            return $record->payed_money - $record->earned_value;
                        }
                        return 0;
                    })
                    ->numeric(decimalPlaces: 0, decimalSeparator: ',', thousandsSeparator: ' ')
                    ->color('danger')
                    ->description(function ($record) {
                        // Бонусом можем выводить процент комиссии мелким текстом под суммой
                        if ($record->payed_money > 0 && $record->earned_value > 0) {
                            $diff = $record->payed_money - $record->earned_value;
                            $percent = ($diff / $record->payed_money) * 100;
                            return round($percent, 1) . '%';
                        }
                        return null;
                    }),

                // Добавили скрываемые колонки аналитики для таблицы
                TextColumn::make('promocode')
                    ->label('Промокод')
                    ->badge()
                    ->color('warning')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('utm_source')
                    ->label('UTM Source')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('gc_created_at')->label('Создан')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->defaultSort('gc_created_at', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Grid::make(3)
                    ->schema([
                        // ЛЕВАЯ И ЦЕНТРАЛЬНАЯ КОЛОНКА
                        Grid::make(1)->schema([
                            Section::make('Основная информация')
                                ->icon('heroicon-o-information-circle')
                                ->schema([
                                    TextEntry::make('gc_number')->label('Номер заказа (ГК)'),

                                    TextEntry::make('status')
                                        ->label('Статус')
                                        ->badge()
                                        ->color(fn (string $state): string => match ($state) {
                                            'Завершен', 'payed' => 'success',
                                            'В работе', 'Частично оплачен', 'in_work', 'part_payed' => 'warning',
                                            'Отменен', 'Ложный', 'cancelled', 'false' => 'danger',
                                            default => 'gray',
                                        }),

                                    TextEntry::make('gc_created_at')
                                        ->label('Дата создания')
                                        ->dateTime('d.m.Y H:i'),

                                    TextEntry::make('gc_paid_at')
                                        ->label('Дата оплаты')
                                        ->dateTime('d.m.Y H:i')
                                        ->placeholder('Не оплачено'),
                                ])->columns(2),

                            Section::make('Состав заказа')
                                ->icon('heroicon-o-shopping-bag')
                                ->schema([
                                    TextEntry::make('products.title')
                                        ->label('Привязанные продукты')
                                        ->badge()
                                        ->color('info')
                                        ->placeholder('Продукты не найдены'),
                                ]),

                            // НОВЫЙ БЛОК С АНАЛИТИКОЙ ЗАКАЗА
                            Section::make('Аналитика заказа (Last-Touch)')
                                ->icon('heroicon-o-chart-bar')
                                ->schema([
                                    TextEntry::make('promocode')
                                        ->label('Промокод')
                                        ->badge()
                                        ->color('warning')
                                        ->placeholder('—'),
                                    TextEntry::make('utm_source')->label('UTM Source')->placeholder('—'),
                                    TextEntry::make('utm_medium')->label('UTM Medium')->placeholder('—'),
                                    TextEntry::make('utm_campaign')->label('UTM Campaign')->placeholder('—'),
                                    TextEntry::make('utm_term')->label('UTM Term')->placeholder('—'),
                                    TextEntry::make('utm_content')->label('UTM Content')->placeholder('—'),
                                ])->columns(3),

                        ])->columnSpan(2),

                        // ПРАВАЯ КОЛОНКА
                        Grid::make(1)->schema([
                            Section::make('Финансы')
                                ->icon('heroicon-o-banknotes')
                                ->schema([
                                    TextEntry::make('cost')
                                        ->label('Общая стоимость')
                                        ->money('RUB')
                                        ->weight('bold'),

                                    TextEntry::make('payed_money')
                                        ->label('Фактически оплачено')
                                        ->money('RUB')
                                        ->color('success')
                                        ->weight('bold'),

                                    TextEntry::make('earned_value')
                                        ->label('Чистая прибыль (Получено)')
                                        ->numeric(decimalPlaces: 0, decimalSeparator: ',', thousandsSeparator: ' ')
                                        ->color('success')
                                        ->weight('bold'),

                                    TextEntry::make('commission')
                                        ->label('Комиссия ПС/Рассрочки')
                                        ->state(function ($record) {
                                            if ($record->payed_money > 0 && $record->earned_value > 0) {
                                                return $record->payed_money - $record->earned_value;
                                            }
                                            return 0;
                                        })
                                        ->numeric(decimalPlaces: 0, decimalSeparator: ',', thousandsSeparator: ' ')
                                        ->color('danger'),

                                    TextEntry::make('left_to_pay')
                                        ->label('Осталось доплатить')
                                        ->money('RUB')
                                        ->state(function ($record) {
                                            return max(0, $record->cost - $record->payed_money);
                                        })
                                        ->color(fn ($state) => $state > 0 ? 'danger' : 'gray'),
                                ]),

                            Section::make('Клиент и Менеджер')
                                ->icon('heroicon-o-user')
                                ->schema([
                                    TextEntry::make('client.email')
                                        ->label('Email клиента')
                                        ->url(fn ($record) => $record->client ? UnifiedClientResource::getUrl('view', ['record' => $record->client->id]) : null)
                                        ->color('primary'),

                                    TextEntry::make('manager.name') // Поправил на правильную связь
                                    ->label('Менеджер')
                                        ->placeholder('Без менеджера'),
                                ]),
                        ])->columnSpan(1),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDeals::route('/'),
            'create' => Pages\CreateDeal::route('/create'),
            'view' => Pages\ViewDeal::route('/{record}'),
            'edit' => Pages\EditDeal::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            // Указываем полный путь, начиная с корневого слеша (App\...)
            \App\Filament\Admin\Resources\DealResource\RelationManagers\PaymentsRelationManager::class,
        ];
    }
}