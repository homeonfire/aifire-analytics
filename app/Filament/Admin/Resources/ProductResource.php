<?php

namespace App\Filament\Admin\Resources;

use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube'; // Иконка коробки
    protected static ?string $navigationLabel = 'Продукты и Лид-магниты';
    protected static ?string $modelLabel = 'Продукт';
    protected static ?string $pluralModelLabel = 'Продукты';
    protected static ?int $navigationSort = 4; // Будет под заказами

    // Пробиваем права на показ
    public static function canViewAny(): bool
    {
        return true;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('getcourse_id')
                    ->label('ID в GetCourse')
                    ->numeric()
                    ->readOnly() // Защищаем от ручного изменения, это делает скрипт
                    ->placeholder('Заполнится автоматически'),

                TextInput::make('title')
                    ->label('Название в GetCourse')
                    ->required()
                    ->maxLength(255),

                TextInput::make('price')
                    ->label('Базовая стоимость')
                    ->numeric()
                    ->default(0),

                // Тот самый выпадающий список для тегирования!
                Select::make('category')
                    ->label('Категория (Тег)')
                    ->options([
                        'Лид-магнит' => 'Лид-магнит',
                        'Регистрация' => 'Регистрация',
                        'Регистрация на вебинар' => 'Регистрация на вебинар',
                        'Трипваер' => 'Трипваер',
                        'Флагман' => 'Флагман',
                        'Консультация' => 'Консультация',
                        'Клуб по подписке' => 'Клуб по подписке',
                        // --- НОВЫЕ КАТЕГОРИИ ---
                        'Продление' => 'Продление',
                        'Мастер-класс' => 'Мастер-класс',
                        'Бронирование' => 'Бронирование',
                        'Заявка' => 'Заявка',
                        'Разморозка' => 'Разморозка',
                        'Безлимитное продление' => 'Безлимитное продление',
                        'Выдача доступа' => 'Выдача доступа',
                    ])
                    ->searchable()
                    ->nullable(),

                // НОВАЯ ПРИВЯЗКА К ЗАПУСКАМ (Многие-ко-многим)
                Select::make('launches')
                    ->label('Привязка к запускам')
                    ->relationship('launches', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),

                TextColumn::make('getcourse_id')
                    ->label('ID ГК')
                    ->searchable()
                    ->sortable()
                    ->toggleable(), // Можно скрыть по умолчанию, если мешает

                TextColumn::make('title')
                    ->label('Название')
                    ->searchable()
                    ->wrap(),

                // Красивый вывод тега с разными цветами
                TextColumn::make('category')
                    ->label('Категория')
                    ->badge()
                    ->color(fn (string|null $state): string => match ($state) {
                        // Основная воронка
                        'Лид-магнит' => 'gray',
                        'Регистрация', 'Регистрация на вебинар' => 'info',
                        'Трипваер' => 'warning',
                        'Флагман', 'Клуб по подписке' => 'success',
                        'Консультация', 'Мастер-класс' => 'primary',

                        // Новые (технические и доп. продажи)
                        'Продление', 'Безлимитное продление' => 'success',
                        'Бронирование', 'Заявка' => 'warning',
                        'Разморозка', 'Выдача доступа' => 'gray',

                        default => 'danger', // То, что еще без категории
                    })
                    ->default('Не размечено')
                    ->sortable(),

                TextColumn::make('price')
                    ->label('Цена')
                    ->money('RUB')
                    ->sortable(),

                // Бонус: считаем, сколько раз этот продукт встречается в заказах
                TextColumn::make('deals_count')
                    ->counts('deals')
                    ->label('Кол-во покупок/регистраций')
                    ->sortable(),

                // ВЫВОД ПРИВЯЗАННЫХ ЗАПУСКОВ
                TextColumn::make('launches.name')
                    ->label('Запуски')
                    ->searchable()
                    ->badge()
                    ->separator(', ') // Если запусков несколько, разделит запятой
                    ->color('info'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Фильтр по категории')
                    ->options([
                        'Лид-магнит' => 'Лид-магнит',
                        'Регистрация' => 'Регистрация',
                        'Регистрация на вебинар' => 'Регистрация на вебинар',
                        'Трипваер' => 'Трипваер',
                        'Флагман' => 'Флагман',
                        'Консультация' => 'Консультация',
                        'Клуб по подписке' => 'Клуб по подписке',
                        'Продление' => 'Продление',
                        'Мастер-класс' => 'Мастер-класс',
                        'Бронирование' => 'Бронирование',
                        'Заявка' => 'Заявка',
                        'Разморозка' => 'Разморозка',
                        'Безлимитное продление' => 'Безлимитное продление',
                        'Выдача доступа' => 'Выдача доступа',
                    ]),

                // НОВЫЙ ФИЛЬТР ПО ЗАПУСКАМ ЧЕРЕЗ СВЯЗЬ
                Tables\Filters\SelectFilter::make('launches')
                    ->label('Фильтр по запуску')
                    ->relationship('launches', 'name')
                    ->multiple()
                    ->preload(),
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

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Admin\Resources\ProductResource\Pages\ListProducts::route('/'),
            'create' => \App\Filament\Admin\Resources\ProductResource\Pages\CreateProduct::route('/create'),
            'edit' => \App\Filament\Admin\Resources\ProductResource\Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}