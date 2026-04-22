<?php

namespace App\Filament\Resources\UnifiedClientResource\Pages;

use App\Filament\Resources\UnifiedClientResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewUnifiedClient extends ViewRecord
{
    protected static string $resource = UnifiedClientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
