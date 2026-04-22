<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Filament\Facades\Filament;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

class Integration extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-link';
    protected static ?string $navigationLabel = 'Интеграция';
    protected static ?string $title = 'Настройка интеграций';
    protected static ?string $navigationGroup = 'Настройки';

    protected static string $view = 'filament.admin.pages.integration';

    public ?array $data = [];

    public function mount(): void
    {
        $school = Filament::getTenant();

        // Заполняем форму текущими данными школы
        $this->form->fill([
            'getcourse_domain' => $school?->getcourse_domain,
            'getcourse_api_key' => $school?->getcourse_api_key,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Новое API GetCourse (REST)')
                    ->description('Введите данные для подключения к новому API GetCourse. Это нужно для автоматической загрузки тарифов и синхронизации данных.')
                    ->icon('heroicon-o-server-stack')
                    ->schema([
                        TextInput::make('getcourse_domain')
                            ->label('Домен (Аккаунт) школы')
                            ->placeholder('name.getcourse.ru')
                            ->helperText('Укажите домен без https://')
                            ->required(),

                        TextInput::make('getcourse_api_key')
                            ->label('Секретный ключ (API Key)')
                            ->password()
                            ->revealable()
                            ->required(),
                    ])->columns(2)
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $school = Filament::getTenant();

        if ($school) {
            $school->update($this->form->getState());

            Notification::make()
                ->title('Настройки API сохранены')
                ->success()
                ->send();
        }
    }

    // Генерируем ссылки с новыми параметрами
    protected function getViewData(): array
    {
        $school = Filament::getTenant();
        $uuid = $school ? $school->uuid : 'ОШИБКА_UUID';

        // НОВЫЙ ВЕБХУК ЗАКАЗОВ: добавлены offers, promocode, user UTMs и deal UTMs
        $ordersUrl = "https://aifire-tech.ru/api/webhooks/{$uuid}/getcourse/orders?"
            . "email={object.user.email}&phone={object.user.phone}&first_name={object.user.first_name}&last_name={object.user.last_name}"
            . "&getcourse_id={object.user.id}&city={object.user.city}&avatar={object.user.avatar_url}&sb_id={object.user.sb_id}"

            // First-touch (UTM пользователя)
            . "&utm_source={object.user.utm_source}&utm_medium={object.user.utm_medium}&utm_campaign={object.user.utm_campaign}&utm_term={object.user.utm_term}&utm_content={object.user.utm_content}"

            // Last-touch (UTM заказа)
            . "&deal_utm_source={object.deal_utm_source}&deal_utm_medium={object.deal_utm_medium}&deal_utm_campaign={object.deal_utm_campaign}&deal_utm_term={object.deal_utm_term}&deal_utm_content={object.deal_utm_content}"

            // Данные заказа
            . "&gc_number={object.number}&offers={object.offers}&promocode={object.promocode}&status={object.status}"
            . "&cost={object.cost_money_value}&payed_money={object.payed_money}"
            . "&created_at={object.created_at format='Y-m-d H:i:s'}&payed_at={object.payed_at format='Y-m-d H:i:s'}"

            // Менеджер
            . "&manager_name={object.manager}&manager_email={manager_email}&manager_phone={manager_phone}";

        // Пользователи (оставляем как есть, тут только First-touch UTM)
        $usersUrl = "https://aifire-tech.ru/api/webhooks/{$uuid}/getcourse/users?"
            . "email={email}&phone={phone}&first_name={first_name}&last_name={last_name}&getcourse_id={id}&sb_id={sb_id}"
            . "&utm_source={utm_source}&utm_medium={utm_medium}&utm_campaign={utm_campaign}&utm_term={utm_term}&utm_content={utm_content}";

        return [
            'ordersUrl' => $ordersUrl,
            'usersUrl' => $usersUrl,
            'schoolName' => $school ? $school->name : 'Неизвестный проект',
        ];
    }
}