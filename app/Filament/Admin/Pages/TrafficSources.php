<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use App\Models\UnifiedClient;
use Illuminate\Support\Facades\DB;
use Filament\Facades\Filament;

class TrafficSources extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-chart-pie'; // Иконка диаграммы
    protected static ?string $navigationLabel = 'Источники трафика';
    protected static ?string $title = 'Аналитика по источникам';
    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.admin.pages.traffic-sources';

    public function table(Table $table): Table
    {
        // 1. Получаем ID текущей выбранной школы (проекта)
        $schoolId = Filament::getTenant()?->id;

        return $table
            ->query(
                UnifiedClient::query()
                    // 2. Изолируем данные: берем клиентов только из этой школы
                    ->where('school_id', $schoolId)
                    ->select(
                        DB::raw('MIN(id) as id'),
                        'utm_source',
                        'utm_medium', // Добавили поле medium
                        DB::raw('COUNT(id) as clients_count'),
                        DB::raw('SUM(total_spent) as total_revenue')
                    )
                    // Группируем по связке Источник + Канал
                    ->groupBy('utm_source', 'utm_medium')
            )
            ->columns([
                TextColumn::make('utm_source')
                    ->label('Источник (Source)')
                    ->state(fn ($record) => empty($record->utm_source) ? 'Без источника' : $record->utm_source)
                    ->badge()
                    ->color(fn ($state) => $state === 'Без источника' ? 'gray' : 'primary'),

                // Выводим новую колонку
                TextColumn::make('utm_medium')
                    ->label('Канал (Medium)')
                    ->state(fn ($record) => empty($record->utm_medium) ? '-' : $record->utm_medium)
                    ->badge()
                    ->color('info'),

                TextColumn::make('clients_count')
                    ->label('Количество клиентов')
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('total_revenue')
                    ->label('Общая выручка')
                    ->money('RUB')
                    ->sortable()
                    ->color('success')
                    ->weight('bold'),
            ])
            ->defaultSort('total_revenue', 'desc') // Теперь логичнее по умолчанию сортировать по деньгам
            ->paginated(false);
    }
}