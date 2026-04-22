<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\WebinarResource\Pages;
use App\Models\Webinar;
use App\Models\Product;
use App\Models\Launch; // <-- ДОБАВИЛИ МОДЕЛЬ LAUNCH
use App\Jobs\ImportWebinarJob;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use App\Filament\Admin\Resources\WebinarResource\RelationManagers\AttendancesRelationManager;
use Filament\Facades\Filament;

class WebinarResource extends Resource
{
    protected static ?string $model = Webinar::class;
    protected static ?string $navigationLabel = 'Вебинары';
    protected static ?string $modelLabel = 'Вебинар';
    protected static ?string $pluralModelLabel = 'Вебинары';
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-bar';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('cohort')
                    ->label('Запуск (Поток)')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable()
                    ->default('-'),

                Tables\Columns\TextColumn::make('started_at')
                    ->label('Дата')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('attendances_count')
                    ->counts('attendances')
                    ->label('Зрителей')
                    ->badge(),
            ])
            ->defaultSort('started_at', 'desc')
            ->headerActions([
                Action::make('importBizon')
                    ->label('Импорт из Bizon365')
                    ->color('primary')
                    ->icon('heroicon-m-arrow-up-tray')
                    ->form([
                        FileUpload::make('file')
                            ->label('Файл отчета (.html)')
                            ->required()
                            ->disk('public')
                            ->directory('webinar-imports'),

                        TextInput::make('title')
                            ->label('Название вебинара')
                            ->helperText('Если оставить пустым, возьмем из файла'),

                        // ОБНОВЛЕНО: Теперь берем список из новой таблицы Запусков
                        Select::make('cohort')
                            ->label('Привязать к запуску (потоку)')
                            ->options(function () {
                                $schoolId = Filament::getTenant()?->id;
                                // Берем название запуска как ключ и как значение
                                return Launch::where('school_id', $schoolId)
                                    ->pluck('name', 'name')
                                    ->toArray();
                            })
                            ->searchable()
                            ->nullable(),
                    ])
                    ->action(function (array $data) {
                        $cohort = $data['cohort'] ?? null;

                        $schoolId = Filament::getTenant()?->id;

                        ImportWebinarJob::dispatch($data['file'], $data['title'] ?? null, $cohort, $schoolId);

                        Notification::make()
                            ->title('Импорт запущен')
                            ->info()
                            ->body('Система обрабатывает файл. Данные появятся в списке через несколько минут.')
                            ->send();
                    })
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // Базовая информация
                Infolists\Components\Section::make('Основная информация')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Infolists\Components\TextEntry::make('title')
                            ->label('Название вебинара')
                            ->weight('bold')
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large),
                        Infolists\Components\TextEntry::make('cohort')
                            ->label('Запуск (Поток)')
                            ->badge()
                            ->color('info')
                            ->default('Без привязки'),
                        Infolists\Components\TextEntry::make('started_at')
                            ->label('Дата проведения')
                            ->dateTime('d.m.Y H:i'),
                        Infolists\Components\TextEntry::make('duration_minutes')
                            ->label('Длительность (фактическая)')
                            ->suffix(' мин.'),
                    ])->columns(4),

                // БЛОК ГЛУБОКОЙ АНАЛИТИКИ
                Infolists\Components\Section::make('Аналитика вовлеченности')
                    ->icon('heroicon-o-chart-pie')
                    ->schema([
                        Infolists\Components\TextEntry::make('attendances_count')
                            ->state(fn ($record) => $record->attendances()->count())
                            ->label('Всего уникальных зрителей')
                            ->badge()
                            ->color('success'),

                        Infolists\Components\TextEntry::make('avg_watch_time')
                            ->state(fn ($record) => round($record->attendances()->avg('total_minutes')) . ' мин.')
                            ->label('Среднее время просмотра')
                            ->badge()
                            ->color('info'),

                        Infolists\Components\TextEntry::make('retention')
                            ->state(function ($record) {
                                $total = $record->attendances()->count();
                                if (!$total || !$record->duration_minutes) return '0%';

                                // Считаем тех, кто просидел больше 80% времени вебинара (округляем для Postgres)
$eightyPercent = (int) round($record->duration_minutes * 0.8);
$loyal = $record->attendances()->where('total_minutes', '>=', $eightyPercent)->count();
                                $percent = round(($loyal / $total) * 100);
                                return "{$percent}% ({$loyal} чел.)";
                            })
                            ->label('Доживаемость (>80% эфира)')
                            ->badge()
                            ->color(fn ($state) => (int)$state >= 30 ? 'success' : 'warning'),

                        Infolists\Components\TextEntry::make('peak_join')
                            ->state(function ($record) {
                                $peaks = \App\Models\WebinarAttendanceInterval::whereHas('attendance', fn($q) => $q->where('webinar_id', $record->id))
                                    ->selectRaw('SUBSTR(CAST(entered_at AS TEXT), 1, 5) as offset_time, COUNT(*) as count')
                                    ->groupBy('offset_time')
                                    ->orderByDesc('count')
                                    ->limit(3)
                                    ->get();

                                if ($peaks->isEmpty()) return 'Нет данных';

                                $startedAt = \Carbon\Carbon::parse($record->started_at);

                                return $peaks->map(function($p) use ($startedAt) {
                                    $parts = explode(':', $p->offset_time);
                                    $hours = (int)$parts[0];
                                    $minutes = (int)($parts[1] ?? 0);

                                    $realTime = $startedAt->copy()->addHours($hours)->addMinutes($minutes)->format('H:i');

                                    return "<b>{$realTime}</b> (на {$p->offset_time} эфира) — {$p->count} чел.";
                                })->implode('<br>');
                            })
                            ->label('Пик заходов в комнату (Топ-3)')
                            ->html(),

                        Infolists\Components\TextEntry::make('peak_leave')
                            ->state(function ($record) {
                                $peaks = \App\Models\WebinarAttendanceInterval::whereHas('attendance', fn($q) => $q->where('webinar_id', $record->id))
                                    ->selectRaw('SUBSTR(CAST(left_at AS TEXT), 1, 5) as offset_time, COUNT(*) as count')
                                    ->groupBy('offset_time')
                                    ->get();

                                if ($peaks->isEmpty()) return 'Нет данных';

                                $cutoffMinutes = $record->duration_minutes - 15; // Отсекаем финальные 15 минут
                                $startedAt = \Carbon\Carbon::parse($record->started_at);

                                $validPeaks = $peaks->filter(function ($peak) use ($cutoffMinutes) {
                                    $parts = explode(':', $peak->offset_time);
                                    $hours = (int)$parts[0];
                                    $minutes = (int)($parts[1] ?? 0);

                                    $totalMinutes = ($hours * 60) + $minutes;

                                    return $totalMinutes < $cutoffMinutes;
                                });

                                $top = $validPeaks->sortByDesc('count')->take(3);

                                if ($top->isEmpty()) return 'Отвалов до конца эфира не было';

                                return $top->map(function($p) use ($startedAt) {
                                    $parts = explode(':', $p->offset_time);
                                    $hours = (int)$parts[0];
                                    $minutes = (int)($parts[1] ?? 0);

                                    $realTime = $startedAt->copy()->addHours($hours)->addMinutes($minutes)->format('H:i');

                                    return "<b>{$realTime}</b> (на {$p->offset_time} эфира) — {$p->count} чел.";
                                })->implode('<br>');
                            })
                            ->label('Пики выходов (Главные отвалы)')
                            ->html()
                            ->color('danger'),

                        Infolists\Components\TextEntry::make('mobile_percent')
                            ->state(function ($record) {
                                $total = $record->attendances()->count();
                                if (!$total) return '0%';
                                $mob = $record->attendances()->where('device', 'моб')->count();
                                return round(($mob / $total) * 100) . '%';
                            })
                            ->label('Доля мобильного трафика')
                            ->icon('heroicon-m-device-phone-mobile'),

                        Infolists\Components\TextEntry::make('bouncers')
                            ->state(function ($record) {
                                $total = $record->attendances()->count();
                                if (!$total) return '0%';

                                $bouncers = $record->attendances()->where('total_minutes', '<', 10)->count();
                                $percent = round(($bouncers / $total) * 100);

                                return "{$percent}% ({$bouncers} чел.)";
                            })
                            ->label('Быстрые отвалы (< 10 мин)')
                            ->color('warning'),

                        Infolists\Components\TextEntry::make('top_cities')
                            ->state(function ($record) {
                                $cities = $record->attendances()
                                    ->whereNotNull('city')
                                    ->where('city', '!=', '')
                                    ->selectRaw('city, COUNT(*) as count')
                                    ->groupBy('city')
                                    ->orderByDesc('count')
                                    ->limit(3)
                                    ->get();

                                if ($cities->isEmpty()) return 'Нет данных';

                                return $cities->map(fn($c) => "<b>{$c->city}</b>: {$c->count} чел.")->implode('<br>');
                            })
                            ->label('Топ-3 города')
                            ->icon('heroicon-o-map-pin')
                            ->html(),

                        Infolists\Components\TextEntry::make('chat_stats')
                            ->state(function ($record) {
                                $totalMessages = \App\Models\WebinarChatMessage::whereHas('attendance', function ($q) use ($record) {
                                    $q->where('webinar_id', $record->id);
                                })->count();

                                return $totalMessages > 0 ? "{$totalMessages} сообщений" : 'Чат пуст';
                            })
                            ->label('Объем чата')
                            ->icon('heroicon-o-chat-bubble-left-right')
                            ->color('info'),
                    ])->columns(3),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWebinars::route('/'),
            'view' => Pages\ViewWebinar::route('/{record}'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            AttendancesRelationManager::class,
        ];
    }
}