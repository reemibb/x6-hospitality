<?php

namespace App\Filament\Resources\PropertyResource\Pages;

use App\Filament\Resources\PropertyResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist; 

class ViewProperty extends ViewRecord
{
    protected static string $resource = PropertyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Property Overview')
                    ->schema([
                        Infolists\Components\ImageEntry::make('images')
                            ->label('Property Images')
                            ->columnSpanFull(),
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('title')
                                    ->size('lg')
                                    ->weight('bold'),
                                Infolists\Components\TextEntry::make('host.name')
                                    ->label('Property Host'),
                            ]),
                        Infolists\Components\TextEntry::make('description')
                            ->columnSpanFull(),
                    ]),
                
                Infolists\Components\Section::make('Property Details')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('type')
                                    ->badge()
                                    ->color('primary'),
                                Infolists\Components\TextEntry::make('status')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'active' => 'success',
                                        'inactive' => 'danger',
                                        'maintenance' => 'warning',
                                    }),
                                Infolists\Components\IconEntry::make('featured')
                                    ->boolean()
                                    ->trueIcon('heroicon-o-star')
                                    ->trueColor('warning'),
                            ]),
                        Infolists\Components\Grid::make(4)
                            ->schema([
                                Infolists\Components\TextEntry::make('price_per_night')
                                    ->money('USD')
                                    ->label('Price per Night'),
                                Infolists\Components\TextEntry::make('max_guests')
                                    ->label('Max Guests'),
                                Infolists\Components\TextEntry::make('bedrooms'),
                                Infolists\Components\TextEntry::make('bathrooms'),
                            ]),
                    ]),

                Infolists\Components\Section::make('Location')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('country'),
                                Infolists\Components\TextEntry::make('city'),
                                Infolists\Components\TextEntry::make('address')
                                    ->columnSpan(2),
                            ]),
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('latitude')
                                    ->placeholder('Not specified'),
                                Infolists\Components\TextEntry::make('longitude')
                                    ->placeholder('Not specified'),
                            ]),
                    ]),

                Infolists\Components\Section::make('Amenities')
                    ->schema([
                        Infolists\Components\TextEntry::make('amenities')
                            ->badge()
                            ->color('info')
                            ->columnSpanFull(),
                    ])
                    ->collapsed(),

                Infolists\Components\Section::make('System Information')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('created_at')
                                    ->dateTime()
                                    ->label('Created'),
                                Infolists\Components\TextEntry::make('updated_at')
                                    ->dateTime()
                                    ->label('Last Updated'),
                            ]),
                    ])
                    ->collapsed(),
            ]);
    }
}
