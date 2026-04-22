<?php

namespace App\Filament\Admin\Resources\UtmPresetResource\Pages;

use App\Filament\Admin\Resources\UtmPresetResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageUtmPresets extends ManageRecords
{
    protected static string $resource = UtmPresetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
