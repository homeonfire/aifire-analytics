<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\Product;
use App\Models\Launch;
use Livewire\Attributes\On;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;

class ProductMixTable extends BaseWidget
{
    protected static ?string $heading = 'Срез по тарифам и продуктам (ABC-анализ)';
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
        $query = Product::query()->where('id', 0); // По умолчанию пустой запрос
        $launch = Launch::find($this->launchId);

        if ($launch && $schoolId) {

            // УМНЫЙ ФИЛЬТР: динамически применяет даты запуска в зависимости от категории текущего продукта
            $applyDateFilters = function (Builder $q) use ($launch) {
                $q->where(function (Builder $sub) use ($launch) {

                    // 1. Если продукт - Трипваер
                    $sub->orWhere(function (Builder $cond) use ($launch) {
                        $cond->where('products.category', 'Трипваер');
                        if ($launch->tripwire_start) $cond->where('deals.gc_created_at', '>=', $launch->tripwire_start);
                        if ($launch->tripwire_end) $cond->where('deals.gc_created_at', '<=', $launch->tripwire_end);
                    });

                    // 2. Если продукт - Бронирование
                    $sub->orWhere(function (Builder $cond) use ($launch) {
                        $cond->where('products.category', 'Бронирование');
                        if ($launch->booking_start) $cond->where('deals.gc_created_at', '>=', $launch->booking_start);
                        if ($launch->booking_end) $cond->where('deals.gc_created_at', '<=', $launch->booking_end);
                    });

                    // 3. Если продукт - Флагман или Клуб
                    $sub->orWhere(function (Builder $cond) use ($launch) {
                        $cond->whereIn('products.category', ['Флагман', 'Клуб по подписке']);
                        if ($launch->flagship_start) $cond->where('deals.gc_created_at', '>=', $launch->flagship_start);
                        if ($launch->flagship_end) $cond->where('deals.gc_created_at', '<=', $launch->flagship_end);
                    });

                    // 4. Регистрации (собираем всё, что привязано к запуску, без окон дат)
                    $sub->orWhereIn('products.category', ['Регистрация', 'Регистрация на вебинар', 'Лид-магнит']);

                    // 5. Все остальные категории (по умолчанию применяем к ним окна Флагмана)
                    $sub->orWhere(function (Builder $cond) use ($launch) {
                        $cond->whereNotIn('products.category', [
                            'Трипваер', 'Бронирование', 'Флагман', 'Клуб по подписке',
                            'Регистрация', 'Регистрация на вебинар', 'Лид-магнит'
                        ]);
                        if ($launch->flagship_start) $cond->where('deals.gc_created_at', '>=', $launch->flagship_start);
                        if ($launch->flagship_end) $cond->where('deals.gc_created_at', '<=', $launch->flagship_end);
                    });
                });
            };

            $query = Product::query()
                ->where('school_id', $schoolId)
                // Оставляем только те продукты, которые привязаны к текущему Запуску
                ->whereHas('launches', fn($q) => $q->where('launches.id', $launch->id))
                // Оставляем продукты, по которым была хотя бы одна заявка в рамках наших окон конверсии
                ->whereHas('deals', function ($q) use ($applyDateFilters) {
                    $applyDateFilters($q);
                })
                // Подсчет общего количества заявок
                ->withCount([
                    'deals as total_deals' => function (Builder $q) use ($applyDateFilters) {
                        $applyDateFilters($q);
                    },
                    // Подсчет успешных оплат
                    'deals as paid_deals' => function (Builder $q) use ($applyDateFilters) {
                        $q->where('deals.payed_money', '>', 0);
                        $applyDateFilters($q);
                    }
                ])
                // Суммирование ГРЯЗНОЙ выручки
                ->withSum([
                    'deals as total_revenue' => function (Builder $q) use ($applyDateFilters) {
                        $q->where('deals.payed_money', '>', 0);
                        $applyDateFilters($q);
                    }
                ], 'deals.payed_money')
                // Суммирование ЧИСТАЙ прибыли
                ->withSum([
                    'deals as net_revenue' => function (Builder $q) use ($applyDateFilters) {
                        $q->where('deals.payed_money', '>', 0);
                        $applyDateFilters($q);
                    }
                ], 'deals.earned_value');
        }

        return $table
            ->query($query)
            ->groups([
                Tables\Grouping\Group::make('category')
                    ->label('Категория')
                    ->getTitleFromRecordUsing(fn (Product $record): string => $record->category ?: 'Без категории')
                    ->collapsible(),
            ])
            ->defaultGroup('category')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->state(fn ($record) => $record->title)
                    ->label('Название продукта / тарифа')
                    ->searchable(['title'])
                    ->wrap(),

                Tables\Columns\TextColumn::make('category')
                    ->label('Категория')
                    ->badge()
                    ->color(fn (string|null $state): string => match ($state) {
                        'Лид-магнит' => 'gray',
                        'Регистрация', 'Регистрация на вебинар' => 'info',
                        'Трипваер' => 'warning',
                        'Флагман', 'Клуб по подписке' => 'success',
                        'Консультация', 'Мастер-класс' => 'primary',
                        'Продление', 'Безлимитное продление' => 'success',
                        'Бронирование', 'Заявка' => 'warning',
                        'Разморозка', 'Выдача доступа' => 'gray',
                        default => 'danger',
                    })
                    ->default('Не размечено'),

                Tables\Columns\TextColumn::make('total_deals')
                    ->label('Заявок (шт)')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('paid_deals')
                    ->label('Оплат (шт)')
                    ->sortable()
                    ->badge()
                    ->color('success')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('conversion')
                    ->label('Конверсия')
                    ->state(function ($record) {
                        if (!$record->total_deals) return '0%';
                        return round(($record->paid_deals / $record->total_deals) * 100, 1) . '%';
                    })
                    ->badge()
                    ->color(fn ($state) => (float)$state >= 30 ? 'success' : ((float)$state >= 10 ? 'warning' : 'danger'))
                    ->alignCenter(),

                // НОВАЯ КОЛОНКА ЧИСТОЙ ПРИБЫЛИ
                Tables\Columns\TextColumn::make('net_revenue')
                    ->label('Чистыми')
                    ->money('RUB')
                    ->sortable()
                    ->color('success')
                    ->weight('bold')
                    ->alignRight(),

                // СТАРАЯ КОЛОНКА ГРЯЗНОЙ ВЫРУЧКИ
                Tables\Columns\TextColumn::make('total_revenue')
                    ->label('Грязными')
                    ->money('RUB')
                    ->sortable()
                    ->color('gray')
                    ->alignRight(),
            ])
            ->defaultSort('net_revenue', 'desc') // Теперь топ-продукты определяются по чистой прибыли
            ->paginated(false);
    }
}