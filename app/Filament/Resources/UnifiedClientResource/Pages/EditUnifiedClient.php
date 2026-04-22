<?php

namespace App\Filament\Resources\UnifiedClientResource\Pages;

use App\Filament\Resources\UnifiedClientResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUnifiedClient extends EditRecord
{
    protected static string $resource = UnifiedClientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
