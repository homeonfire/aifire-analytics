<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\Deal;
use App\Models\Launch;
use Livewire\Attributes\On;
use Illuminate\Database\Eloquent\Builder;
use Filament\Facades\Filament;

class UtmPerformanceTable extends BaseWidget
{
    protected static ?string $heading = 'First-Touch: Детализация по UTM-меткам клиентов';
    protected int | string | array $columnSpan = 'full';
    protected static bool $isDiscovered = false;
    protected static bool $isLazy = false;

    public ?int $launchId = null;

    public function mount(): void
    {
        $schoolId = Filament::getTenant()?->id;
        $defaultLaunch = Launch::where('school_id', $schoolId)->latest()->first();
        $this->launchId = $defaultLaunch?->id;
    }

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
            $query->join('unified_clients', 'deals.unified_client_id', '=', 'unified_clients.id')
                ->where('deals.school_id', $schoolId)
                ->where(function (Builder $q) use ($launch) {

                    // 1. РЕГИСТРАЦИИ
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
                        if ($launch->tripwire_start) $subQ->where('deals.gc_created_at', '>=', $launch->tripwire_start);
                        if ($launch->tripwire_end) $subQ->where('deals.gc_created_at', '<=', $launch->tripwire_end);
                    });

                    // 3. БРОНИРОВАНИЯ
                    $q->orWhere(function (Builder $subQ) use ($launch) {
                        $subQ->whereHas('products', function($p) use ($launch) {
                            $p->where('category', 'Бронирование')
                                ->whereHas('launches', fn($l) => $l->where('launches.id', $launch->id));
                        });
                        if ($launch->booking_start) $subQ->where('deals.gc_created_at', '>=', $launch->booking_start);
                        if ($launch->booking_end) $subQ->where('deals.gc_created_at', '<=', $launch->booking_end);
                    });

                    // 4. ФЛАГМАНЫ И КЛУБЫ
                    $q->orWhere(function (Builder $subQ) use ($launch) {
                        $subQ->whereHas('products', function($p) use ($launch) {
                            $p->whereNotIn('category', ['Регистрация', 'Регистрация на вебинар', 'Лид-магнит', 'Трипваер', 'Бронирование'])
                                ->whereHas('launches', fn($l) => $l->where('launches.id', $launch->id));
                        });
                        if ($launch->flagship_start) $subQ->where('deals.gc_created_at', '>=', $launch->flagship_start);
                        if ($launch->flagship_end) $subQ->where('deals.gc_created_at', '<=', $launch->flagship_end);
                    });

                })
                ->select(
                    'unified_clients.utm_source',
                    'unified_clients.utm_medium',
                    'unified_clients.utm_campaign'
                )
                // Искусственный ID для Filament
                ->selectRaw('MAX(deals.id) as id')
                ->selectRaw('COUNT(deals.id) as total_deals')
                ->selectRaw('SUM(CASE WHEN deals.payed_money > 0 THEN 1 ELSE 0 END) as paid_deals')
                ->selectRaw('SUM(deals.payed_money) as total_revenue')
                ->selectRaw('SUM(deals.earned_value) as net_revenue') // <--- ДОБАВИЛИ ЧИСТУЮ ПРИБЫЛЬ
                ->groupBy('unified_clients.utm_source', 'unified_clients.utm_medium', 'unified_clients.utm_campaign');
        } else {
            $query->where('deals.id', 0);
        }

        return $table
            ->query($query)
            ->columns([
                Tables\Columns\TextColumn::make('utm_source')
                    ->label('Источник (Source)')
                    ->badge()
                    ->color('info')
                    ->default('Органика'),

                Tables\Columns\TextColumn::make('utm_medium')
                    ->label('Канал (Medium)')
                    ->default('-'),

                Tables\Columns\TextColumn::make('utm_campaign')
                    ->label('Кампания (Campaign)')
                    ->default('-')
                    ->wrap(),

                Tables\Columns\TextColumn::make('total_deals')
                    ->label('Всего заявок')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('paid_deals')
                    ->label('Оплат (шт)')
                    ->badge()
                    ->color('success')
                    ->sortable()
                    ->alignCenter(),

                // НОВАЯ КОЛОНКА ЧИСТОЙ ПРИБЫЛИ
                Tables\Columns\TextColumn::make('net_revenue')
                    ->label('Чистая прибыль')
                    ->money('RUB')
                    ->color('success')
                    ->weight('bold')
                    ->sortable()
                    ->alignRight(),

                // СТАРАЯ КОЛОНКА ПЕРЕКРАШЕНА
                Tables\Columns\TextColumn::make('total_revenue')
                    ->label('Грязными')
                    ->money('RUB')
                    ->color('gray')
                    ->sortable()
                    ->alignRight(),
            ])
            ->defaultSort('net_revenue', 'desc') // Сортируем самые прибыльные кампании наверх
            ->paginated([5, 10, 25]);
    }
}