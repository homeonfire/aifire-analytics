<?php

namespace App\Filament\Resources\UnifiedClientResource\Pages;

use App\Filament\Resources\UnifiedClientResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUnifiedClients extends ListRecords
{
    protected static string $resource = UnifiedClientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
