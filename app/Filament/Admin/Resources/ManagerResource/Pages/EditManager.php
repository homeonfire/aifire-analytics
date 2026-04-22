<?php

namespace App\Filament\Admin\Resources\ManagerResource\Pages;

use App\Filament\Admin\Resources\ManagerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditManager extends EditRecord
{
    protected static string $resource = ManagerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
