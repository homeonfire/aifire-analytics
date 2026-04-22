<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\SchoolResource\Pages;
use App\Models\School;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SchoolResource extends Resource
{
    protected static ?string $model = School::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationLabel = 'Школы (SaaS)';
    protected static ?string $navigationGroup = 'Система';

    // ВАЖНО: Отключаем фильтрацию по текущей школе,
    // чтобы суперадмин видел ВСЕ школы в базе, а не только текущую
    protected static bool $isScopedToTenant = false;

    // Скрываем этот раздел от всех сотрудников.
    // Он будет виден только пользователю с ID = 1 (тебе)
    public static function canViewAny(): bool
    {
        return auth()->id() === 1;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Название школы')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('uuid')
                            ->label('UUID (для вебхуков)')
                            ->disabled()
                            ->dehydrated(false)
                            ->hiddenOn('create') // При создании он генерируется сам, скрываем
                            ->helperText('Используйте этот код в ссылках для вебхуков на GetCourse и Tilda.'),
                    ]),

                Forms\Components\Section::make('Доступ сотрудников')
                    ->schema([
                        // Магическое поле: автоматически подтягивает пользователей и пишет их в school_user
                        Forms\Components\Select::make('members')
                            ->label('Сотрудники школы')
                            ->relationship('members', 'email') // Ищем сотрудников по Email
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->helperText('Выберите зарегистрированных пользователей, у которых будет доступ к этой школе.'),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Название')
                    ->searchable(),

                // Фишка: Копирование UUID по клику!
                Tables\Columns\TextColumn::make('uuid')
                    ->label('UUID (Скопировать)')
                    ->copyable()
                    ->copyMessage('UUID скопирован в буфер')
                    ->icon('heroicon-m-clipboard-document')
                    ->color('primary'),

                Tables\Columns\TextColumn::make('members_count')
                    ->counts('members')
                    ->label('Сотрудников')
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d.m.Y H:i')
                    ->label('Создана')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            'index' => Pages\ListSchools::route('/'),
            'create' => Pages\CreateSchool::route('/create'),
            'edit' => Pages\EditSchool::route('/{record}/edit'),
        ];
    }
}