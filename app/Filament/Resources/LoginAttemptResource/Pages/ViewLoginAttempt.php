<?php

namespace App\Filament\Resources\LoginAttemptResource\Pages;

use App\Filament\Resources\LoginAttemptResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewLoginAttempt extends ViewRecord
{
    protected static string $resource = LoginAttemptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}