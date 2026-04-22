<?php

namespace App\Filament\Admin\Pages\Tenancy;

use App\Models\School;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Tenancy\RegisterTenant;
use Illuminate\Support\HtmlString;

class RegisterSchool extends RegisterTenant
{
    // Заголовок страницы и кнопки в меню
    public static function getLabel(): string
    {
        return 'Создать новую школу';
    }

    // Форма создания (нам нужно только имя, UUID сгенерируется сам)
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Название школы или проекта')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    // Логика сохранения
    protected function handleRegistration(array $data): School
    {
        // Создаем школу
        $school = School::create($data);

        // Привязываем текущего пользователя к созданной школе как владельца/сотрудника
        $school->members()->attach(auth()->user());

        return $school;
    }

    // Тот самый текст-подсказка под заголовком
    public function getSubheading(): string | HtmlString | null
    {
        $userEmail = auth()->user()->email;

        return new HtmlString("
            Создайте свою первую школу или проект для начала работы. 
            <br><br>
            <span style='color: #6b7280; font-size: 0.9em;'>
                <strong>Вы сотрудник?</strong><br>
                Не создавайте школу. Просто передайте ваш Email (<strong>{$userEmail}</strong>) владельцу платформы или руководителю, чтобы он добавил вас в существующий проект.
            </span>
        ");
    }
}