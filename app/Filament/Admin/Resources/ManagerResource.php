<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ManagerResource\Pages;
use App\Models\Manager;
use App\Models\Deal;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Admin\Resources\ManagerResource\RelationManagers\DealsRelationManager;
use App\Filament\Admin\Resources\ManagerResource\RelationManagers\AbcProductsRelationManager;
class ManagerResource extends Resource
{
    protected static ?string $model = Manager::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Отдел продаж';
    protected static ?string $modelLabel = 'Менеджер';
    protected static ?string $pluralModelLabel = 'Менеджеры';
    protected static ?string $navigationGroup = 'Аналитика и Продажи';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount('deals as total_deals')
            ->withCount(['deals as in_progress_deals' => fn($q) => $q->where('payed_money', 0)])
            ->withCount(['deals as full_paid_deals' => fn($q) => $q->whereRaw('payed_money >= cost')->where('payed_money', '>', 0)])
            ->withCount(['deals as partial_paid_deals' => fn($q) => $q->whereRaw('payed_money < cost')->where('payed_money', '>', 0)])
            ->withSum('deals as total_revenue', 'payed_money')
            ->withSum('deals as net_revenue', 'earned_value')
            // СЧИТАЕМ СРЕДНЕЕ ВРЕМЯ ЗАКРЫТИЯ СДЕЛКИ В МИНУТАХ (от создания до оплаты)
            ->addSelect(['avg_payment_time_minutes' => Deal::selectRaw('AVG(EXTRACT(EPOCH FROM (gc_paid_at - gc_created_at)) / 60)')
                ->whereColumn('manager_id', 'managers.id')
                ->whereNotNull('gc_paid_at')
                ->whereNotNull('gc_created_at')
                ->whereRaw('gc_paid_at >= gc_created_at') // Защита от отрицательных значений
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Данные менеджера')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Имя и Фамилия')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->label('Телефон')
                            ->tel()
                            ->maxLength(255),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        // Функция для красивого форматирования минут в дни и часы
        $formatTime = function (?float $minutes) {
            if (!$minutes || $minutes <= 0) return '-';
            $d = floor($minutes / 1440);
            $h = floor(($minutes % 1440) / 60);
            $m = floor($minutes % 60);

            $res = [];
            if ($d > 0) $res[] = "{$d}д";
            if ($h > 0) $res[] = "{$h}ч";
            if ($m > 0 && $d == 0) $res[] = "{$m}м"; // Минуты показываем только если сделка закрыта быстрее дня

            return implode(' ', $res) ?: '< 1м';
        };

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Менеджер')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (Manager $record): string => $record->email ?? $record->phone ?? 'Нет контактов'),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Работает')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_deals')
                    ->label('Всего заявок')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('in_progress_deals')
                    ->label('В работе')
                    ->badge()
                    ->color('gray')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('full_paid_deals')
                    ->label('Полных оплат')
                    ->badge()
                    ->color('success')
                    ->sortable()
                    ->alignCenter(),

                // ВЫВЕЛИ ЧАСТИЧНЫЕ ОПЛАТЫ
                Tables\Columns\TextColumn::make('partial_paid_deals')
                    ->label('Частичных')
                    ->badge()
                    ->color('warning')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('conversion')
                    ->label('Конверсия')
                    ->state(function (Manager $record) {
                        if (!$record->total_deals) return '0%';
                        $paid = $record->full_paid_deals + $record->partial_paid_deals;
                        return round(($paid / $record->total_deals) * 100, 1) . '%';
                    })
                    ->badge()
                    ->color('info')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('avg_payment_time_minutes')
                    ->label('Цикл сделки')
                    ->state(fn ($record) => $formatTime($record->avg_payment_time_minutes))
                    ->badge()
                    ->color('warning')
                    ->sortable()
                    ->alignCenter()
                    ->tooltip('Среднее время от падения заявки до поступления денег'),

                // НОВАЯ КОЛОНКА (Чистая прибыль)
                Tables\Columns\TextColumn::make('net_revenue')
                    ->label('Чистыми (₽)')
                    ->numeric(decimalPlaces: 0, decimalSeparator: ',', thousandsSeparator: ' ')
                    ->color('success')
                    ->weight('bold')
                    ->sortable()
                    ->alignRight(),

                Tables\Columns\TextColumn::make('total_revenue')
                    ->label('Грязными (₽)')
                    ->numeric(decimalPlaces: 0, decimalSeparator: ',', thousandsSeparator: ' ')
                    ->color('success')
                    ->weight('bold')
                    ->sortable()
                    ->alignRight(),
            ])
            ->defaultSort('total_revenue', 'desc')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        $formatTime = function (?float $minutes) {
            if (!$minutes || $minutes <= 0) return 'Нет данных';
            $d = floor($minutes / 1440);
            $h = floor(($minutes % 1440) / 60);
            $m = floor($minutes % 60);

            $res = [];
            if ($d > 0) $res[] = "{$d} д.";
            if ($h > 0) $res[] = "{$h} ч.";
            if ($m > 0 && $d == 0) $res[] = "{$m} мин.";

            return implode(' ', $res) ?: 'Меньше минуты';
        };

        return $infolist
            ->schema([
                Infolists\Components\Section::make('Профиль менеджера')
                    ->schema([
                        Infolists\Components\TextEntry::make('name')->label('Имя')->weight('bold')->size(Infolists\Components\TextEntry\TextEntrySize::Large),
                        Infolists\Components\TextEntry::make('email')->label('Email')->icon('heroicon-m-envelope'),
                        Infolists\Components\TextEntry::make('phone')->label('Телефон')->icon('heroicon-m-phone'),
                        Infolists\Components\TextEntry::make('getcourse_id')->label('GetCourse ID')->badge()->color('info'),
                    ])->columns(4),

                Infolists\Components\Section::make('Эффективность (Общая)')
                    ->schema([
                        Infolists\Components\TextEntry::make('total_deals')->label('Заявок в базе')->badge(),
                        Infolists\Components\TextEntry::make('in_progress_deals')->label('В работе')->badge()->color('gray'),
                        Infolists\Components\TextEntry::make('full_paid_deals')->label('Полных оплат')->badge()->color('success'),
                        Infolists\Components\TextEntry::make('partial_paid_deals')->label('Частичных оплат')->badge()->color('warning'),

                        // НОВОЕ ПОЛЕ В КАРТОЧКЕ
                        Infolists\Components\TextEntry::make('avg_payment_time_minutes')
                            ->label('Ср. время закрытия (Цикл сделки)')
                            ->state(fn ($record) => $formatTime($record->avg_payment_time_minutes))
                            ->badge()
                            ->color('warning'),

                        // ВЫВЕЛИ ЧИСТУЮ
                        Infolists\Components\TextEntry::make('net_revenue')
                            ->label('Чистая прибыль (₽)')
                            ->numeric(decimalPlaces: 0, decimalSeparator: ',', thousandsSeparator: ' ')
                            ->color('success')
                            ->weight('bold'),

                        // ВЫВЕЛИ ГРЯЗНУЮ
                        Infolists\Components\TextEntry::make('total_revenue')
                            ->label('Грязная выручка (₽)')
                            ->numeric(decimalPlaces: 0, decimalSeparator: ',', thousandsSeparator: ' ')
                            ->color('gray'),
                    ])->columns(6),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // Добавили ManagerResource\ перед RelationManagers
            AbcProductsRelationManager::class,

            // Если вкладка со сделками тоже лежит внутри папки менеджера,
            // у нее тоже должна быть эта приставка:
            DealsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListManagers::route('/'),
            'view' => Pages\ViewManager::route('/{record}'),
            'edit' => Pages\EditManager::route('/{record}/edit'),
        ];
    }
}