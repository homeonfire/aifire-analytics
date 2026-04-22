<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Facades\Filament;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class Employees extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Сотрудники';
    protected static ?string $title = 'Команда проекта';
    protected static ?string $navigationGroup = 'Настройки';

    protected static string $view = 'filament.admin.pages.employees';

    public function table(Table $table): Table
    {
        $school = Filament::getTenant();

        return $table
            ->query(
            // Выводим только тех пользователей, которые привязаны к текущей школе
                User::query()->whereHas('schools', function (Builder $query) use ($school) {
                    $query->where('schools.id', $school->id);
                })
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Имя')
                    ->searchable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('created_at')
                    ->label('Добавлен в систему')
                    ->dateTime('d.m.Y H:i'),
            ])
            ->headerActions([
                Action::make('addMember')
                    ->label('Добавить сотрудника')
                    ->icon('heroicon-m-plus')
                    ->color('primary')
                    ->form([
                        Select::make('user_id')
                            ->label('Email зарегистрированного сотрудника')
                            ->searchable()
                            // Умный поиск по базе: ищем по Email и отсекаем тех, кто уже в проекте
                            ->getSearchResultsUsing(function (string $search) use ($school) {
                                $currentMemberIds = $school->members()->pluck('users.id')->toArray();

                                return User::whereNotIn('id', $currentMemberIds)
                                    ->where('email', 'like', "%{$search}%")
                                    ->limit(10)
                                    ->pluck('email', 'id')
                                    ->toArray();
                            })
                            ->getOptionLabelUsing(fn ($value): ?string => User::find($value)?->email)
                            ->required()
                            ->helperText('Начните вводить email. Сотрудник должен предварительно пройти регистрацию на платформе.'),
                    ])
                    ->action(function (array $data) use ($school) {
                        // Привязываем пользователя к школе
                        $school->members()->attach($data['user_id']);
                    })
            ])
            ->actions([
                Action::make('removeMember')
                    ->label('Забрать доступ')
                    ->color('danger')
                    ->icon('heroicon-m-x-mark')
                    ->requiresConfirmation()
                    ->modalHeading('Удалить из проекта?')
                    ->modalDescription('Пользователь больше не сможет просматривать дашборды и данные этой школы.')
                    ->action(function (User $record) use ($school) {
                        // Отвязываем пользователя от школы
                        $school->members()->detach($record->id);
                    })
                    // Защита от дурака: скрываем кнопку удаления для самого себя, чтобы случайно не отрезать себе доступ
                    ->hidden(fn (User $record) => $record->id === auth()->id()),
            ]);
    }
}