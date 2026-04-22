<?php

namespace App\Filament\Admin\Resources\ManagerResource\Pages;

use App\Filament\Admin\Resources\ManagerResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewManager extends ViewRecord
{
    protected static string $resource = ManagerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    // ВЫВОДИМ НАШ НОВЫЙ ВИДЖЕТ ВНИЗУ КАРТОЧКИ
//    protected function getFooterWidgets(): array
//    {
//        return [
//            ManagerResource\Widgets\ManagerProductsTableWidget::class,
//        ];
//    }
}
