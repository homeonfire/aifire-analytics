<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Webinar;
use App\Models\WebinarAttendance;
use App\Models\Launch;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Livewire\Attributes\On;
use App\Filament\Admin\Resources\WebinarResource;
use Filament\Facades\Filament;

class LaunchWebinarsTable extends BaseWidget
{
    protected static bool $isDiscovered = false;
    protected int | string | array $columnSpan = 'full';
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
        $launch = Launch::find($this->launchId);

        $orderedWebinarIds = [];
        $query = Webinar::query()->where('id', 0); // По умолчанию пустая таблица

        if ($launch && $schoolId) {
            // Собираем ID вебинаров строго по имени выбранного Запуска
            $orderedWebinarIds = Webinar::where('school_id', $schoolId)
                ->where('cohort', $launch->name)
                ->orderBy('started_at', 'asc')
                ->pluck('id')
                ->toArray();

            // Изолируем основной запрос таблицы
            $query = Webinar::query()
                ->where('school_id', $schoolId)
                ->where('cohort', $launch->name);
        }

        return $table
            ->query($query)
            ->defaultSort('started_at', 'asc')
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Название')
                    ->searchable(),

                Tables\Columns\TextColumn::make('started_at')
                    ->label('Дата')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('attendances_count')
                    ->counts('attendances')
                    ->label('Зрителей всего')
                    ->badge()
                    ->color('success'),

                // --- КОЛОНКА 1: ШАГ ВОРОНКИ ---
                Tables\Columns\TextColumn::make('from_prev')
                    ->label('Конверсия шага')
                    ->state(function ($record) use ($orderedWebinarIds) {
                        if (empty($orderedWebinarIds)) return '-';

                        $currentIndex = array_search($record->id, $orderedWebinarIds);
                        if ($currentIndex === 0 || $currentIndex === false) {
                            return 'Старт (100%)';
                        }

                        $survivors = null;
                        for ($i = 0; $i < $currentIndex; $i++) {
                            $attendees = WebinarAttendance::where('webinar_id', $orderedWebinarIds[$i])
                                ->pluck('unified_client_id')
                                ->toArray();

                            $survivors = ($survivors === null) ? $attendees : array_intersect($survivors, $attendees);
                        }

                        $prevCount = count($survivors);
                        if ($prevCount === 0) return '0%';

                        $retainedCount = WebinarAttendance::where('webinar_id', $record->id)
                            ->whereIn('unified_client_id', $survivors)
                            ->count();

                        return round(($retainedCount / $prevCount) * 100) . '% (' . $retainedCount . ' чел.)';
                    })
                    ->badge()
                    ->color('warning'),

                // --- КОЛОНКА 2: ГЛОБАЛЬНОЕ ЯДРО ---
                Tables\Columns\TextColumn::make('from_first')
                    ->label('Ядро с 1-го дня')
                    ->state(function ($record) use ($orderedWebinarIds) {
                        if (empty($orderedWebinarIds)) return '-';

                        $currentIndex = array_search($record->id, $orderedWebinarIds);
                        if ($currentIndex === 0 || $currentIndex === false) {
                            return '-';
                        }

                        $firstWebinarId = $orderedWebinarIds[0];
                        $firstCount = WebinarAttendance::where('webinar_id', $firstWebinarId)->count();
                        if ($firstCount === 0) return '0%';

                        $survivors = null;
                        for ($i = 0; $i < $currentIndex; $i++) {
                            $attendees = WebinarAttendance::where('webinar_id', $orderedWebinarIds[$i])
                                ->pluck('unified_client_id')
                                ->toArray();

                            $survivors = ($survivors === null) ? $attendees : array_intersect($survivors, $attendees);
                        }

                        $retainedCount = WebinarAttendance::where('webinar_id', $record->id)
                            ->whereIn('unified_client_id', $survivors)
                            ->count();

                        return round(($retainedCount / $firstCount) * 100) . '% (' . $retainedCount . ' чел.)';
                    })
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('avg_time')
                    ->label('Ср. время')
                    ->state(fn ($record) => round($record->attendances()->avg('total_minutes')) . ' мин.')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('retention')
                    ->label('Досидели (>80%)')
                    ->state(function ($record) {
                        $total = $record->attendances()->count();
                        if (!$total || !$record->duration_minutes) return '0%';
                        $loyal = $record->attendances()->where('total_minutes', '>=', (int) round($record->duration_minutes * 0.8))->count();
                        return round(($loyal / $total) * 100) . '%';
                    })
                    ->badge()
                    ->color(fn ($state) => (int)$state >= 30 ? 'success' : 'gray'),
            ])
            ->recordUrl(
                fn (Webinar $record): string => WebinarResource::getUrl('view', ['record' => $record])
            )
            ->heading('Воронка доходимости вебинаров потока')
            ->emptyStateHeading('Нет вебинаров')
            ->emptyStateDescription('Для этого запуска еще не загружено ни одного отчета.');
    }
}