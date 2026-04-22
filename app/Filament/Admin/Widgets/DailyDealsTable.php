<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\Deal;
use App\Models\Launch;
use Livewire\Attributes\On;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;

class DailyDealsTable extends BaseWidget
{
    protected static ?string $heading = 'Детализация по дням (с учетом окон запуска)';
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
            $query->where('school_id', $schoolId)
                ->where(function (Builder $q) use ($launch) {

                    // 1. ТРИПВАЕРЫ
                    $q->orWhere(function (Builder $subQ) use ($launch) {
                        $subQ->whereHas('products', function(Builder $p) use ($launch) {
                            $p->where('category', 'Трипваер')
                                ->whereHas('launches', fn($l) => $l->where('launches.id', $launch->id));
                        });
                        if ($launch->tripwire_start) $subQ->where('gc_created_at', '>=', $launch->tripwire_start);
                        if ($launch->tripwire_end) $subQ->where('gc_created_at', '<=', $launch->tripwire_end);
                    });

                    // 2. БРОНИРОВАНИЯ
                    $q->orWhere(function (Builder $subQ) use ($launch) {
                        $subQ->whereHas('products', function(Builder $p) use ($launch) {
                            $p->where('category', 'Бронирование')
                                ->whereHas('launches', fn($l) => $l->where('launches.id', $launch->id));
                        });
                        if ($launch->booking_start) $subQ->where('gc_created_at', '>=', $launch->booking_start);
                        if ($launch->booking_end) $subQ->where('gc_created_at', '<=', $launch->booking_end);
                    });

                    // 3. ФЛАГМАНЫ
                    $q->orWhere(function (Builder $subQ) use ($launch) {
                        $subQ->whereHas('products', function(Builder $p) use ($launch) {
                            $p->whereIn('category', ['Флагман', 'Клуб по подписке'])
                                ->whereHas('launches', fn($l) => $l->where('launches.id', $launch->id));
                        });
                        if ($launch->flagship_start) $subQ->where('gc_created_at', '>=', $launch->flagship_start);
                        if ($launch->flagship_end) $subQ->where('gc_created_at', '<=', $launch->flagship_end);
                    });

                })
                ->selectRaw('DATE(gc_created_at) as created_day')
                ->selectRaw('DATE(gc_created_at) as id')
                ->selectRaw('COUNT(*) as total_created')
                ->selectRaw('SUM(cost) as expected_revenue')
                ->selectRaw('SUM(CASE WHEN payed_money > 0 THEN 1 ELSE 0 END) as total_paid')
                ->selectRaw('SUM(payed_money) as actual_revenue')
                ->selectRaw('SUM(earned_value) as net_revenue') // <--- ДОБАВИЛИ ЧИСТУЮ ПРИБЫЛЬ
                ->whereNotNull('gc_created_at')
                ->groupBy('created_day');
        } else {
            $query->where('id', 0);
        }

        return $table
            ->query($query)
            ->columns([
                Tables\Columns\TextColumn::make('created_day')->label('Дата')->dateTime('d.m.Y')->sortable()->color('primary'),
                Tables\Columns\TextColumn::make('total_created')->label('Заявок (шт)')->alignCenter(),
                Tables\Columns\TextColumn::make('total_paid')->label('Оплат (шт)')->badge()->color('success')->alignCenter(),

                // ВЫВЕЛИ ЧИСТУЮ ПРИБЫЛЬ
                Tables\Columns\TextColumn::make('net_revenue')->label('Чистая прибыль')->money('RUB')->color('success')->weight('bold'),

                // ПЕРЕИМЕНОВАЛИ И СДЕЛАЛИ СЕРОЙ СТАРУЮ ГРЯЗНУЮ ВЫРУЧКУ
                Tables\Columns\TextColumn::make('actual_revenue')->label('Грязными')->money('RUB')->color('gray'),

                Tables\Columns\TextColumn::make('expected_revenue')->label('Потенциал (Выписано)')->money('RUB')->color('gray'),
            ])
            ->defaultSort('created_day', 'desc')
            ->paginated([7, 14, 30]);
    }
}