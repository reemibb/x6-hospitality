<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoomResource\Pages;
use App\Filament\Resources\RoomResource\RelationManagers;
use App\Models\Room;
use App\Models\Property;
use App\Models\Amenity;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RoomResource extends Resource
{
    protected static ?string $model = Room::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Property Management';
    protected static ?string $recordTitleAttribute = 'room_type';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Room Information')
                    ->schema([
                        Forms\Components\Select::make('property_id')
                            ->label('Property')
                            ->relationship('property', 'title')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpan(2),

                        Forms\Components\Select::make('room_type')
                            ->label('Room Type')
                            ->options([
                                'Single Room' => 'Single Room',
                                'Double Room' => 'Double Room',
                                'Suite' => 'Suite',
                                'Studio' => 'Studio',
                                'Family Room' => 'Family Room',
                                'Deluxe Room' => 'Deluxe Room',
                                'Presidential Suite' => 'Presidential Suite',
                            ])
                            ->required()
                            ->searchable(),

                        Forms\Components\TextInput::make('price_per_night')
                            ->label('Price per Night ($)')
                            ->numeric()
                            ->prefix('$')
                            ->required()
                            ->step(0.01)
                            ->minValue(0),

                        Forms\Components\Textarea::make('description')
                            ->rows(4)
                            ->placeholder('Describe this room...')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Room Amenities')
                    ->schema([
                        Forms\Components\CheckboxList::make('amenities')
                            ->relationship('amenities', 'name', fn ($query) => $query->roomAmenities())
                            ->searchable()
                            ->bulkToggleable()
                            ->columns(3)
                            ->columnSpanFull()
                            ->helperText('Select amenities available in this room'),
                    ])
                    ->collapsible(),

                Forms\Components\Section::make('Photos')
                    ->schema([
                        Forms\Components\FileUpload::make('photos')
                            ->label('Room Photos')
                            ->image()
                            ->multiple()
                            ->directory('rooms')
                            ->visibility('public')
                            ->maxFiles(10)
                            ->reorderable()
                            ->columnSpanFull()
                            ->helperText('Upload up to 10 photos of the room'),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('photos')
                    ->label('Photo')
                    ->getStateUsing(function ($record) {
                        $photos = $record->photos;
                        return is_array($photos) && count($photos) > 0 ? $photos[0] : null;
                    })
                    ->circular()
                    ->size(50)
                    ->defaultImageUrl(url('/images/placeholder-room.png')),

                Tables\Columns\TextColumn::make('property.title')
                    ->label('Property')
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 30 ? $state : null;
                    }),

                Tables\Columns\TextColumn::make('room_type')
                    ->label('Room Type')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Suite' => 'success',
                        'Presidential Suite' => 'danger',
                        'Deluxe Room' => 'warning',
                        'Studio' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('price_per_night')
                    ->label('Price/Night')
                    ->money('USD')
                    ->sortable()
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('amenities_count')
                    ->label('Amenities')
                    ->counts('amenities')
                    ->badge()
                    ->color('info')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('bookings_count')
                    ->label('Bookings')
                    ->counts('bookings')
                    ->badge()
                    ->color('success')
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('property_id')
                    ->label('Property')
                    ->relationship('property', 'title')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('room_type')
                    ->label('Room Type')
                    ->options([
                        'Single Room' => 'Single Room',
                        'Double Room' => 'Double Room',
                        'Suite' => 'Suite',
                        'Studio' => 'Studio',
                        'Family Room' => 'Family Room',
                        'Deluxe Room' => 'Deluxe Room',
                        'Presidential Suite' => 'Presidential Suite',
                    ])
                    ->multiple(),

                Tables\Filters\Filter::make('price_range')
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('price_from')
                                    ->label('Price From ($)')
                                    ->numeric()
                                    ->prefix('$'),
                                Forms\Components\TextInput::make('price_to')
                                    ->label('Price To ($)')
                                    ->numeric()
                                    ->prefix('$'),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['price_from'],
                                fn (Builder $query, $price): Builder => $query->where('price_per_night', '>=', $price),
                            )
                            ->when(
                                $data['price_to'],
                                fn (Builder $query, $price): Builder => $query->where('price_per_night', '<=', $price),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
            
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRooms::route('/'),
            'create' => Pages\CreateRoom::route('/create'),
            'view' => Pages\ViewRoom::route('/{record}'),
            'edit' => Pages\EditRoom::route('/{record}/edit'),
        ];
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with(['property', 'amenities']);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['room_type', 'description', 'property.title'];
    }

    public static function getGlobalSearchResultDetails($record): array
    {
        return [
            'Property' => $record->property?->title,
            'Price' => '$' . number_format($record->price_per_night, 2) . '/night',
            'Type' => $record->room_type,
        ];
    }
}
