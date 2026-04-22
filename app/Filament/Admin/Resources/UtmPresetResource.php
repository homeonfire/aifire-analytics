<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\UtmPresetResource\Pages;
use App\Models\UtmPreset;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class UtmPresetResource extends Resource
{
    protected static ?string $model = UtmPreset::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $tenantOwnershipRelationshipName = 'school';
    protected static ?string $navigationLabel = 'Справочник UTM';
    protected static ?string $modelLabel = 'UTM-метку';
    protected static ?string $pluralModelLabel = 'Справочник UTM';
    protected static ?string $navigationGroup = 'Настройки';

    // Скрываем ресурс из меню, если захотим потом вынести его в отдельный пункт настроек
    // protected static bool $shouldRegisterNavigation = true;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('type')
                    ->label('Группа (Тип метки)')
                    ->options([
                        'utm_source' => 'Источник (utm_source)',
                        'utm_medium' => 'Канал (utm_medium)',
                        'utm_campaign' => 'Кампания (utm_campaign)',
                    ])
                    ->required()
                    ->native(false)
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('label')
                    ->label('Понятное название')
                    ->placeholder('Например: Реклама ВКонтакте')
                    ->helperText('Это название увидят менеджеры в выпадающем списке')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('value')
                    ->label('Техническое значение')
                    ->placeholder('Например: vk_ads')
                    ->helperText('Это значение реально подставится в ссылку')
                    ->required()
                    ->maxLength(255)
                    ->regex('/^[a-zA-Z0-9\-\_]+$/') // Защита от пробелов и кириллицы в ссылках
                    ->validationMessages([
                        'regex' => 'Используйте только латинские буквы, цифры, дефис или подчеркивание.',
                    ]),

                Forms\Components\Toggle::make('is_active')
                    ->label('Активно')
                    ->default(true)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->groups([
                Tables\Grouping\Group::make('type')
                    ->label('Группа')
                    ->getTitleFromRecordUsing(fn (UtmPreset $record): string => match ($record->type) {
                        'utm_source' => '📌 Источники (utm_source)',
                        'utm_medium' => '📢 Каналы (utm_medium)',
                        'utm_campaign' => '🎯 Кампании (utm_campaign)',
                        default => $record->type,
                    })
                    ->collapsible(),
            ])
            ->defaultGroup('type') // По умолчанию таблица сразу разбита на красивые секции
            ->columns([
                Tables\Columns\TextColumn::make('label')
                    ->label('Название (в интерфейсе)')
                    ->weight('bold')
                    ->searchable(),

                Tables\Columns\TextColumn::make('value')
                    ->label('Значение (в ссылке)')
                    ->badge()
                    ->color('gray')
                    ->copyable()
                    ->searchable(),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Включено'),
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
            'index' => Pages\ManageUtmPresets::route('/'),
        ];
    }
}