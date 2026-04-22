<?php

namespace App\Filament\Admin\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\Deal;
use App\Models\Launch;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\TextColumn;
use Livewire\Attributes\On;
use Filament\Facades\Filament;

class ManagerPerformanceTable extends BaseWidget
{
    protected static bool $isDiscovered = false;
    protected static ?string $heading = 'Эффективность менеджеров';
    protected int | string | array $columnSpan = 'full';

    // Отключаем ленивую загрузку
    protected static bool $isLazy = false;

    public ?int $launchId = null;

    public function mount(): void
    {
        $schoolId = Filament::getTenant()?->id;
        $defaultLaunch = Launch::where('school_id', $schoolId)->latest()->first();
        $this->launchId = $defaultLaunch?->id;
    }

    // Слушаем новое событие
    #[On('updateLaunchFilters')]
    public function updateLaunchFilters($launchId): void
    {
        $this->launchId = $launchId;
        $this->resetTable();
    }

    public function table(Table $table): Table
    {
        $schoolId = Filament::getTenant()?->id;
        $query = Deal::query();
        $launch = Launch::find($this->launchId);

        if ($launch && $schoolId) {
            $query = Deal::query()
                ->selectRaw('
                    MAX(id) as id, 
                    manager_name, 
                    
                    -- Всего заказов
                    COUNT(id) as total_deals, 
                    
                    -- В работе (нет оплат)
                    SUM(CASE WHEN payed_money = 0 THEN 1 ELSE 0 END) as in_progress,
                    
                    -- Полные оплаты (оплачено >= стоимости)
                    SUM(CASE WHEN payed_money > 0 AND payed_money >= cost THEN 1 ELSE 0 END) as full_payments,
                    
                    -- Частичные оплаты (предоплаты, рассрочки)
                    SUM(CASE WHEN payed_money > 0 AND payed_money < cost THEN 1 ELSE 0 END) as partial_payments,
                    
                    -- Конверсия в любую оплату
                    (SUM(CASE WHEN payed_money > 0 THEN 1 ELSE 0 END) / COUNT(id)) * 100 as conversion,
                    
                    -- Общая выручка (Грязными)
                    SUM(payed_money) as total_revenue,
                    
                    -- Чистая прибыль (После вычета комиссий)
                    SUM(earned_value) as net_revenue
                ')
                ->where('school_id', $schoolId)
                ->whereNotNull('manager_name')
                ->where('manager_name', '!=', '')
                ->where(function (Builder $q) use ($launch) {

                    // 1. РЕГИСТРАЦИИ (если менеджеры дожимали платные реги)
                    $q->orWhere(function (Builder $subQ) use ($launch) {
                        $subQ->whereHas('products', function($p) use ($launch) {
                            $p->whereIn('category', ['Регистрация', 'Регистрация на вебинар', 'Лид-магнит'])
                                ->whereHas('launches', fn($l) => $l->where('launches.id', $launch->id));
                        });
                    });

                    // 2. ТРИПВАЕРЫ
                    $q->orWhere(function (Builder $subQ) use ($launch) {
                        $subQ->whereHas('products', function($p) use ($launch) {
                            $p->where('category', 'Трипваер')
                                ->whereHas('launches', fn($l) => $l->where('launches.id', $launch->id));
                        });
                        if ($launch->tripwire_start) $subQ->where('gc_created_at', '>=', $launch->tripwire_start);
                        if ($launch->tripwire_end) $subQ->where('gc_created_at', '<=', $launch->tripwire_end);
                    });

                    // 3. БРОНИРОВАНИЯ
                    $q->orWhere(function (Builder $subQ) use ($launch) {
                        $subQ->whereHas('products', function($p) use ($launch) {
                            $p->where('category', 'Бронирование')
                                ->whereHas('launches', fn($l) => $l->where('launches.id', $launch->id));
                        });
                        if ($launch->booking_start) $subQ->where('gc_created_at', '>=', $launch->booking_start);
                        if ($launch->booking_end) $subQ->where('gc_created_at', '<=', $launch->booking_end);
                    });

                    // 4. ФЛАГМАНЫ И КЛУБЫ
                    $q->orWhere(function (Builder $subQ) use ($launch) {
                        $subQ->whereHas('products', function($p) use ($launch) {
                            $p->whereNotIn('category', ['Регистрация', 'Регистрация на вебинар', 'Лид-магнит', 'Трипваер', 'Бронирование'])
                                ->whereHas('launches', fn($l) => $l->where('launches.id', $launch->id));
                        });
                        if ($launch->flagship_start) $subQ->where('gc_created_at', '>=', $launch->flagship_start);
                        if ($launch->flagship_end) $subQ->where('gc_created_at', '<=', $launch->flagship_end);
                    });

                })
                ->groupBy('manager_name');
        } else {
            $query->where('id', 0);
        }

        return $table
            ->query($query)
            ->paginated(false)
            ->defaultSort('net_revenue', 'desc') // Сортируем по чистой прибыли
            ->columns([
                TextColumn::make('manager_name')
                    ->label('Менеджер')
                    ->sortable(),

                TextColumn::make('total_deals')
                    ->label('Всего заявок')
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('in_progress')
                    ->label('В работе')
                    ->sortable()
                    ->badge()
                    ->color('gray')
                    ->alignCenter(),

                TextColumn::make('full_payments')
                    ->label('Полных оплат')
                    ->sortable()
                    ->badge()
                    ->color('success')
                    ->alignCenter(),

                TextColumn::make('partial_payments')
                    ->label('Частичных оплат')
                    ->sortable()
                    ->badge()
                    ->color('warning')
                    ->alignCenter(),

                TextColumn::make('conversion')
                    ->label('Конверсия')
                    ->numeric(decimalPlaces: 1)
                    ->state(fn ($record) => $record->conversion ? $record->conversion . '%' : '0%')
                    ->sortable()
                    ->alignCenter(),

                // НОВАЯ КОЛОНКА ЧИСТОЙ ПРИБЫЛИ
                TextColumn::make('net_revenue')
                    ->label('Чистыми (₽)')
                    ->numeric(decimalPlaces: 0, decimalSeparator: ',', thousandsSeparator: ' ')
                    ->sortable()
                    ->color('success')
                    ->weight('bold')
                    ->alignRight(),

                // СТАРАЯ КОЛОНКА ПЕРЕКРАШЕНА В СЕРЫЙ
                TextColumn::make('total_revenue')
                    ->label('Грязными (₽)')
                    ->numeric(decimalPlaces: 0, decimalSeparator: ',', thousandsSeparator: ' ')
                    ->sortable()
                    ->color('gray')
                    ->alignRight(),
            ]);
    }
}