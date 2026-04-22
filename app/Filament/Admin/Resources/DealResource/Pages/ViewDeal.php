<?php

namespace App\Filament\Admin\Resources\DealResource\Pages;

use App\Filament\Admin\Resources\DealResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewDeal extends ViewRecord
{
    protected static string $resource = DealResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
