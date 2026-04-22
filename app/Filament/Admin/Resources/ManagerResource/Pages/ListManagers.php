<?php

namespace App\Filament\Admin\Resources\ManagerResource\Pages;

use App\Filament\Admin\Resources\ManagerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab; // <-- НЕ ЗАБУДЬ ИМПОРТИРОВАТЬ
use Illuminate\Database\Eloquent\Builder; // <-- НЕ ЗАБУДЬ ИМПОРТИРОВАТЬ

class ListManagers extends ListRecords
{
    protected static string $resource = ManagerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ManagerResource\Widgets\ManagerStatsOverview::class,
        ];
    }

    // НОВЫЙ МЕТОД ДЛЯ ВКЛАДОК
    public function getTabs(): array
    {
        return [
            'active' => Tab::make('В штате (Активные)')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', true))
                ->badge(ManagerResource::getModel()::where('is_active', true)->count()),

            'inactive' => Tab::make('Уволенные / В архиве')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', false)),

            'all' => Tab::make('Все менеджеры'),
        ];
    }
}