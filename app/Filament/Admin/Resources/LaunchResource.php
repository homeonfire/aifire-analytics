<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\LaunchResource\Pages;
use App\Models\Launch;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Facades\Filament;

class LaunchResource extends Resource
{
    protected static ?string $model = Launch::class;

    protected static ?string $navigationIcon = 'heroicon-o-rocket-launch';
    protected static ?string $navigationLabel = 'Запуски';
    protected static ?string $modelLabel = 'Запуск';
    protected static ?string $pluralModelLabel = 'Запуски';
    protected static ?string $navigationGroup = 'Аналитика';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Скрытое поле для привязки к школе (если используешь multi-tenancy)
                Forms\Components\Hidden::make('school_id')
                    ->default(fn () => Filament::getTenant()?->id),

                Forms\Components\Section::make('Основная информация')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Название запуска')
                            ->placeholder('Например: Перепрошивка (14-16 апреля)')
                            ->required()
                            ->maxLength(255),
                    ]),

                Forms\Components\Grid::make(3)->schema([
                    Forms\Components\Section::make('Трипваеры')
                        ->description('Окно учета оплат для недорогих продуктов')
                        ->icon('heroicon-o-bolt')
                        ->schema([
                            Forms\Components\DateTimePicker::make('tripwire_start')
                                ->label('Начало учета'),
                            Forms\Components\DateTimePicker::make('tripwire_end')
                                ->label('Конец учета'),
                        ])->columnSpan(1),

                    Forms\Components\Section::make('Бронирования')
                        ->description('Окно учета предоплат (брони)')
                        ->icon('heroicon-o-bookmark')
                        ->schema([
                            Forms\Components\DateTimePicker::make('booking_start')
                                ->label('Начало учета'),
                            Forms\Components\DateTimePicker::make('booking_end')
                                ->label('Конец учета'),
                        ])->columnSpan(1),

                    Forms\Components\Section::make('Флагманы')
                        ->description('Окно учета оплат основных продуктов')
                        ->icon('heroicon-o-star')
                        ->schema([
                            Forms\Components\DateTimePicker::make('flagship_start')
                                ->label('Начало учета'),
                            Forms\Components\DateTimePicker::make('flagship_end')
                                ->label('Конец учета'),
                        ])->columnSpan(1),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Название запуска')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('tripwire_start')
                    ->label('Старт трипваеров')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('flagship_end')
                    ->label('Конец флагманов')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLaunches::route('/'),
            'create' => Pages\CreateLaunch::route('/create'),
            'edit' => Pages\EditLaunch::route('/{record}/edit'),
        ];
    }
}