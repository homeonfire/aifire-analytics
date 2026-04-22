<?php

namespace App\Filament\Admin\Resources\WebinarResource\Pages;

use App\Filament\Admin\Resources\WebinarResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWebinars extends ListRecords
{
    protected static string $resource = WebinarResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
