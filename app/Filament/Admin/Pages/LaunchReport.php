<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use App\Models\Launch;
use Filament\Facades\Filament;
use Filament\Forms\Get;

class LaunchReport extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';
    protected static ?string $navigationLabel = 'Отчет по запускам';
    protected static ?string $title = 'Аналитика запуска';
    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.admin.pages.launch-report';

    public ?array $data = [];

    public function mount(): void
    {
        $schoolId = Filament::getTenant()?->id;

        // Берем последний созданный запуск по умолчанию
        $defaultLaunch = Launch::where('school_id', $schoolId)->latest()->first();

        $this->form->fill([
            'launch_id' => $defaultLaunch?->id,
        ]);
    }

    public function form(Form $form): Form
    {
        $schoolId = Filament::getTenant()?->id;

        return $form
            ->schema([
                Section::make('Управление отчетом')
                    ->schema([
                        Select::make('launch_id')
                            ->label('Выберите Запуск')
                            ->options(
                                Launch::where('school_id', $schoolId)
                                    ->pluck('name', 'id')
                                    ->toArray()
                            )
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function ($state) {
                                // Сигнализируем виджетам об изменениях, передавая ID запуска
                                $this->dispatch('updateLaunchFilters', launchId: $state);
                            })
                            ->columnSpanFull(),

                        // Информационная панель, показывающая окна конверсии выбранного запуска
                        Grid::make(3)
                            ->schema([
                                Placeholder::make('tripwire_dates')
                                    ->label('Окно Трипваеров')
                                    ->content(function (Get $get) {
                                        $launch = Launch::find($get('launch_id'));
                                        if (!$launch || (!$launch->tripwire_start && !$launch->tripwire_end)) return 'Не задано';
                                        return ($launch->tripwire_start ? $launch->tripwire_start->format('d.m.Y H:i') : '...') . ' — ' . ($launch->tripwire_end ? $launch->tripwire_end->format('d.m.Y H:i') : '...');
                                    }),

                                Placeholder::make('booking_dates')
                                    ->label('Окно Бронирований')
                                    ->content(function (Get $get) {
                                        $launch = Launch::find($get('launch_id'));
                                        if (!$launch || (!$launch->booking_start && !$launch->booking_end)) return 'Не задано';
                                        return ($launch->booking_start ? $launch->booking_start->format('d.m.Y H:i') : '...') . ' — ' . ($launch->booking_end ? $launch->booking_end->format('d.m.Y H:i') : '...');
                                    }),

                                Placeholder::make('flagship_dates')
                                    ->label('Окно Флагманов')
                                    ->content(function (Get $get) {
                                        $launch = Launch::find($get('launch_id'));
                                        if (!$launch || (!$launch->flagship_start && !$launch->flagship_end)) return 'Не задано';
                                        return ($launch->flagship_start ? $launch->flagship_start->format('d.m.Y H:i') : '...') . ' — ' . ($launch->flagship_end ? $launch->flagship_end->format('d.m.Y H:i') : '...');
                                    }),
                            ])
                            ->visible(fn (Get $get) => filled($get('launch_id'))) // Показываем только если запуск выбран
                    ])
            ])
            ->statePath('data');
    }
}