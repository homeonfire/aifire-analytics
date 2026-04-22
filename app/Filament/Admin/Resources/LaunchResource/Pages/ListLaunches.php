<?php

namespace App\Filament\Admin\Resources\LaunchResource\Pages;

use App\Filament\Admin\Resources\LaunchResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLaunches extends ListRecords
{
    protected static string $resource = LaunchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
