<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Пользователи';
    protected static ?string $navigationGroup = 'Система';
    protected static ?int $navigationSort = 2; // Будет отображаться сразу под "Школами"

    // ВАЖНО: Отключаем фильтрацию по текущей школе,
    // чтобы ты видел ВСЕХ зарегистрированных юзеров на платформе
    protected static bool $isScopedToTenant = false;

    // Скрываем раздел от всех, кроме суперадмина (ID = 1)
    public static function canViewAny(): bool
    {
        return auth()->id() === 1;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Данные пользователя')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Имя')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        // Умное поле пароля: обязательно при создании, но при редактировании
                        // можно оставить пустым (тогда пароль не изменится)
                        Forms\Components\TextInput::make('password')
                            ->label('Пароль')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create')
                            ->maxLength(255),
                    ])->columns(2),

                Forms\Components\Section::make('Доступы')
                    ->schema([
                        // Прямо здесь можно дать пользователю доступ к разным школам
                        Forms\Components\Select::make('schools')
                            ->label('Школы (Проекты)')
                            ->relationship('schools', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->helperText('Выберите школы, к данным которых этот сотрудник должен иметь доступ.'),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Имя')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable()
                    ->sortable(),

                // Выводим бейджики со школами, к которым привязан юзер
                Tables\Columns\TextColumn::make('schools.name')
                    ->label('Имеет доступ к школам')
                    ->badge()
                    ->color('info')
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Зарегистрирован')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}