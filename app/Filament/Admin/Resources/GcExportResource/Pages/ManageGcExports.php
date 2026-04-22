<?php

namespace App\Filament\Admin\Resources\GcExportResource\Pages;

use App\Filament\Admin\Resources\GcExportResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageGcExports extends ManageRecords
{
    protected static string $resource = GcExportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
