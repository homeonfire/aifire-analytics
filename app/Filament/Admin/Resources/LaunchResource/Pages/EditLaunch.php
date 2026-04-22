<?php

namespace App\Filament\Admin\Resources\LaunchResource\Pages;

use App\Filament\Admin\Resources\LaunchResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLaunch extends EditRecord
{
    protected static string $resource = LaunchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
