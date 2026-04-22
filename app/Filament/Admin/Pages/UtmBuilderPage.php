<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Components;
use App\Models\UtmPreset;
use Filament\Facades\Filament;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;

class UtmBuilderPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-link';
    protected static ?string $navigationLabel = 'Конструктор UTM';
    protected static ?string $title = 'Генератор UTM-ссылок';
    protected static ?string $navigationGroup = 'Маркетинг';

    // Укажи правильный путь до view, если он создался с другим именем
    protected static string $view = 'filament.admin.pages.utm-builder-page';

    // Сюда будут сохраняться данные формы на лету
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        $schoolId = Filament::getTenant()?->id;

        // Вытягиваем ТОЛЬКО активные метки из нашего справочника
        $sources = UtmPreset::where('school_id', $schoolId)->where('type', 'utm_source')->where('is_active', true)->pluck('label', 'value')->toArray();
        $mediums = UtmPreset::where('school_id', $schoolId)->where('type', 'utm_medium')->where('is_active', true)->pluck('label', 'value')->toArray();
        $campaigns = UtmPreset::where('school_id', $schoolId)->where('type', 'utm_campaign')->where('is_active', true)->pluck('label', 'value')->toArray();

        // Главная функция: она склеивает ссылку каждый раз, когда менеджер что-то меняет
        $updateUrl = function (Get $get, Set $set) {
            $baseUrl = $get('base_url');
            if (!$baseUrl) {
                $set('final_url', null);
                return;
            }

            $params = [];
            if ($s = $get('utm_source')) $params['utm_source'] = $s;
            if ($m = $get('utm_medium')) $params['utm_medium'] = $m;
            if ($c = $get('utm_campaign')) $params['utm_campaign'] = $c;
            if ($t = $get('utm_term')) $params['utm_term'] = $t;
            if ($cnt = $get('utm_content')) $params['utm_content'] = $cnt;

            if (empty($params)) {
                $set('final_url', $baseUrl);
                return;
            }

            // Умная проверка: если в ссылке уже был знак вопроса (например /page?id=1)
            $parsedUrl = parse_url($baseUrl);
            if (isset($parsedUrl['query'])) {
                parse_str($parsedUrl['query'], $existingParams);
                $params = array_merge($existingParams, $params);
                $baseUrl = explode('?', $baseUrl)[0];
            }

            $set('final_url', $baseUrl . '?' . http_build_query($params));
        };

        return $form
            ->schema([
                Components\Section::make('1. Целевая страница')
                    ->schema([
                        Components\TextInput::make('base_url')
                            ->label('Куда ведем трафик? (Ссылка)')
                            ->placeholder('https://aifire-tech.ru/course-name')
                            ->url()
                            ->required()
                            ->live(debounce: 300) // Ждем 300мс после ввода, чтобы обновить ссылку
                            ->afterStateUpdated($updateUrl),
                    ]),

                Components\Section::make('2. Выбор меток (из справочника)')
                    ->description('Метки подгружаются из настроек вашей школы.')
                    ->columns(3)
                    ->schema([
                        Components\Select::make('utm_source')
                            ->label('Источник (utm_source)')
                            ->options($sources)
                            ->searchable()
                            ->live()
                            ->afterStateUpdated($updateUrl),

                        Components\Select::make('utm_medium')
                            ->label('Канал (utm_medium)')
                            ->options($mediums)
                            ->searchable()
                            ->live()
                            ->afterStateUpdated($updateUrl),

                        Components\Select::make('utm_campaign')
                            ->label('Кампания (utm_campaign)')
                            ->options($campaigns)
                            ->searchable()
                            ->live()
                            ->afterStateUpdated($updateUrl),
                    ]),

                Components\Section::make('3. Дополнительные параметры (Ручной ввод)')
                    ->columns(2)
                    ->collapsed() // Прячем под кат по умолчанию, чтобы не пугать новичков
                    ->schema([
                        Components\TextInput::make('utm_term')
                            ->label('Ключевое слово (utm_term)')
                            ->placeholder('Например: target_b2b')
                            ->live(debounce: 500)
                            ->afterStateUpdated($updateUrl),

                        Components\TextInput::make('utm_content')
                            ->label('Содержание (utm_content)')
                            ->placeholder('Например: banner_red')
                            ->live(debounce: 500)
                            ->afterStateUpdated($updateUrl),
                    ]),

                Components\Section::make('Ваша готовая ссылка')
                    ->schema([
                        Components\TextInput::make('final_url')
                            ->label('')
                            ->readOnly() // Менеджер не может сломать ссылку руками
                            ->extraInputAttributes(['style' => 'font-size: 16px; font-weight: bold; color: #10b981; background: #f0fdf4;'])
                            ->suffixAction(
                                Components\Actions\Action::make('copy')
                                    ->icon('heroicon-m-clipboard-document-check')
                                    ->label('Скопировать')
                                    ->action(function ($livewire, $state) {
                                        if ($state) {
                                            // Копируем в буфер через JS
                                            $livewire->js('navigator.clipboard.writeText("'.$state.'")');

                                            // Показываем красивое зеленое уведомление Filament
                                            Notification::make()
                                                ->title('Успешно!')
                                                ->body('Ссылка скопирована в буфер обмена.')
                                                ->success()
                                                ->send();
                                        }
                                    })
                            )
                    ]),
            ])
            ->statePath('data');
    }
}