<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Forms\Components;
use App\Models\Manager;
use App\Models\Deal;
use App\Models\Payment; // <--- ДОБАВИЛИ ИМПОРТ МОДЕЛИ ПЛАТЕЖЕЙ
use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;

class MonthlyManagerReport extends Page implements HasTable, HasForms
{
    use InteractsWithTable, InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Зарплаты и План-факт';
    protected static ?string $title = 'Помесячный отчет (Отдел продаж)';
    protected static ?string $navigationGroup = 'Аналитика и Продажи';
    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.admin.pages.monthly-manager-report';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'selectedMonth' => now()->format('Y-m'),
        ]);
    }

    public function form(Form $form): Form
    {
        $months = collect(range(0, 11))->mapWithKeys(function ($i) {
            $date = now()->subMonths($i);
            return [$date->format('Y-m') => mb_convert_case($date->translatedFormat('F Y'), MB_CASE_TITLE, "UTF-8")];
        })->toArray();

        return $form
            ->schema([
                Components\Select::make('selectedMonth')
                    ->label('Выберите месяц для расчета')
                    ->options($months)
                    ->live()
                    ->afterStateUpdated(fn () => $this->resetTable())
                    ->native(false)
                    ->extraAttributes(['style' => 'max-width: 300px; margin-bottom: 1rem;']),
            ])
            ->statePath('data');
    }

    public function table(Table $table): Table
    {
        // Берем месяц из формы или текущий
        $monthStr = $this->data['selectedMonth'] ?? now()->format('Y-m');

        // Жесткий парсинг первого дня месяца
        $start = Carbon::createFromFormat('Y-m-d', $monthStr . '-01')->startOfDay();
        $end = $start->copy()->endOfMonth();

        $schoolId = Filament::getTenant()?->id;

        return $table
            ->query(
                Manager::query()
                    ->where('school_id', $schoolId)
                    ->select('managers.*') // Выбираем все поля менеджера
                    ->addSelect([
                        // 1. ЗАЯВКИ СЧИТАЕМ ПО СДЕЛКАМ (сколько новых заказов упало в этом месяце)
                        'monthly_deals' => Deal::selectRaw('COUNT(*)')
                            ->whereColumn('deals.manager_id', 'managers.id')
                            ->whereBetween('gc_created_at', [$start, $end]),

                        // 2. ГРЯЗНУЮ ВЫРУЧКУ СЧИТАЕМ ПО ПЛАТЕЖАМ (транзакциям)
                        'monthly_gross' => Payment::selectRaw('COALESCE(SUM(payments.amount), 0)')
                            ->join('deals', 'payments.deal_id', '=', 'deals.id') // Идем от платежа к сделке...
                            ->whereColumn('deals.manager_id', 'managers.id')     // ...а от сделки к менеджеру
                            ->whereBetween('payments.gc_created_at', [$start, $end])
                            // Учитываем только успешные статусы
                            ->whereIn('payments.status', ['Получен', 'accepted', 'Завершен', 'Оплачен']),

                        // 3. ЧИСТУЮ ПРИБЫЛЬ ТОЖЕ СЧИТАЕМ ПО ПЛАТЕЖАМ
                        'monthly_net' => Payment::selectRaw('COALESCE(SUM(payments.net_amount), 0)')
                            ->join('deals', 'payments.deal_id', '=', 'deals.id')
                            ->whereColumn('deals.manager_id', 'managers.id')
                            ->whereBetween('payments.gc_created_at', [$start, $end])
                            ->whereIn('payments.status', ['Получен', 'accepted', 'Завершен', 'Оплачен']),
                    ])
                    // Показываем активных менеджеров ИЛИ тех, кто уже уволен, но в этом месяце получил "хвосты" по рассрочкам
                    // Показываем активных менеджеров ИЛИ тех, кто уже уволен, но в этом месяце получил "хвосты" по рассрочкам
                    ->where(function ($query) use ($start, $end) {
                        $query->where('managers.is_active', true)
                              ->orWhereExists(function ($subQuery) use ($start, $end) {
                                  $subQuery->selectRaw('1')
                                      ->from('payments')
                                      ->join('deals', 'payments.deal_id', '=', 'deals.id')
                                      ->whereColumn('deals.manager_id', 'managers.id')
                                      ->whereBetween('payments.gc_created_at', [$start, $end])
                                      ->whereIn('payments.status', ['Получен', 'accepted', 'Завершен', 'Оплачен']);
                              });
                    })
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Менеджер')
                    ->weight('bold')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('monthly_deals')
                    ->label('Взято в работу (шт)')
                    ->alignCenter()
                    ->sortable()
                    ->tooltip('Количество новых заказов, закрепленных за менеджером в этом месяце'),

                Tables\Columns\TextColumn::make('monthly_net')
                    ->label('Чистая прибыль (₽)')
                    ->money('RUB')
                    ->color('success')
                    ->weight('bold')
                    ->sortable()
                    ->alignRight()
                    ->tooltip('Сумма фактических транзакций (за вычетом комиссий) за этот месяц'),

                Tables\Columns\TextColumn::make('monthly_gross')
                    ->label('Грязная выручка (₽)')
                    ->money('RUB')
                    ->color('gray')
                    ->sortable()
                    ->alignRight(),
            ])
            ->defaultSort('monthly_net', 'desc')
            ->paginated(false);
    }
}