<?php

namespace App\Filament\Resources\RoomResource\Pages;

use App\Filament\Resources\RoomResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewRoom extends ViewRecord
{
    protected static string $resource = RoomResource::class;

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
                Infolists\Components\Section::make('Room Details')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('room_type')
                                    ->label('Room Type')
                                    ->badge()
                                    ->size('lg'),
                                Infolists\Components\TextEntry::make('price_per_night')
                                    ->label('Price per Night')
                                    ->money('USD')
                                    ->size('lg'),
                            ]),
                        Infolists\Components\TextEntry::make('property.title')
                            ->label('Property'),
                        Infolists\Components\TextEntry::make('description')
                            ->placeholder('No description provided')
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('Photos')
                    ->schema([
                        Infolists\Components\ImageEntry::make('photos')
                            ->label('')
                            ->getStateUsing(fn ($record) => $record->photos ?? [])
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(fn ($record) => empty($record->photos)),

                Infolists\Components\Section::make('Amenities')
                    ->schema([
                        Infolists\Components\TextEntry::make('amenities.name')
                            ->label('')
                            ->badge()
                            ->separator(',')
                            ->placeholder('No amenities assigned'),
                    ])
                    ->collapsible(),

                Infolists\Components\Section::make('Statistics')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('bookings_count')
                                    ->label('Total Bookings')
                                    ->getStateUsing(fn ($record) => $record->bookings()->count())
                                    ->badge()
                                    ->color('success'),
                                Infolists\Components\TextEntry::make('amenities_count')
                                    ->label('Amenities')
                                    ->getStateUsing(fn ($record) => $record->amenities()->count())
                                    ->badge()
                                    ->color('info'),
                            ]),
                    ]),

                Infolists\Components\Section::make('Property Reviews')
                    ->schema([
                        Infolists\Components\TextEntry::make('property_reviews_count')
                            ->label('Property Reviews')
                            ->getStateUsing(fn ($record) => $record->property?->reviews()->count() ?? 0)
                            ->badge()
                            ->color('warning')
                            ->helperText('Reviews are for the entire property, not individual rooms'),
                    ])
                    ->collapsible(),

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