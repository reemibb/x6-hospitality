<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PropertyResource\Pages;
use App\Filament\Resources\PropertyResource\RelationManagers;
use App\Models\Property;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use App\Models\User;

class PropertyResource extends Resource
{
    protected static ?string $model = Property::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Property Management';
    protected static ?string $recordTitleAttribute = 'title';


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Basic Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('host_id')
                                    ->label('Property Host')
                                    ->options(User::whereIn('role', ['admin', 'host'])->pluck('name', 'id'))
                                    ->required()
                                    ->searchable(),
                                Forms\Components\Select::make('type')
                                    ->options([
                                        'apartment' => 'Apartment',
                                        'house' => 'House',
                                        'villa' => 'Villa',
                                        'studio' => 'Studio',
                                        'condo' => 'Condo',
                                        'townhouse' => 'Townhouse',
                                        'penthouse' => 'Penthouse',
                                    ])
                                    ->required(),
                            ]),
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('description')
                            ->required()
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),

                Section::make('Location')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('country')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('city')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('address')
                                    ->required()
                                    ->maxLength(500)
                                    ->columnSpan(2),
                            ]),
                        Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('latitude')
                                    ->numeric()
                                    ->step(0.0000001)
                                    ->placeholder('e.g., 40.7128'),
                                Forms\Components\TextInput::make('longitude')
                                    ->numeric()
                                    ->step(0.0000001)
                                    ->placeholder('e.g., -74.0060'),
                            ]),
                    ]),

                Section::make('Property Details')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                Forms\Components\TextInput::make('price_per_night')
                                    ->required()
                                    ->numeric()
                                    ->prefix('$')
                                    ->step(0.01),
                                Forms\Components\TextInput::make('max_guests')
                                    ->required()
                                    ->numeric()
                                    ->minValue(1),
                                Forms\Components\TextInput::make('bedrooms')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0),
                                Forms\Components\TextInput::make('bathrooms')
                                    ->required()
                                    ->numeric()
                                    ->step(0.5)
                                    ->minValue(0.5),
                            ]),
                    ]),

                Section::make('Amenities & Features')
                    ->schema([
                        Forms\Components\CheckboxList::make('amenities')
                            ->relationship('amenities', 'name', fn ($query) => $query->propertyAmenities())
                            ->searchable()
                            ->bulkToggleable()
                            ->columns(3)
                            ->columnSpanFull()
                            ->helperText('Select amenities available at this property'),
                    ]),

                Section::make('Images')
                    ->schema([
                        Forms\Components\FileUpload::make('images')
                            ->label('Property Images')
                            ->multiple()
                            ->image()
                            ->maxFiles(10)
                            ->reorderable()
                            ->columnSpanFull()
                            ->directory('properties')
                            ->visibility('public'),
                    ]),

                Section::make('Status & Settings')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('status')
                                    ->options([
                                        'active' => 'Active',
                                        'inactive' => 'Inactive', 
                                        'maintenance' => 'Under Maintenance',
                                    ])
                                    ->required()
                                    ->default('active'),
                                Forms\Components\Toggle::make('featured')
                                    ->label('Featured Property')
                                    ->helperText('Featured properties appear first in search results'),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('images')
                    ->label('Image')
                    ->circular()
                    ->stacked()
                    ->limit(3)
                    ->limitedRemainingText(),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 30) {
                            return null;
                        }
                        return $state;
                    }),
                Tables\Columns\TextColumn::make('host.name')
                    ->label('Host')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('type')
                    ->colors([
                        'primary' => 'apartment',
                        'success' => 'house',
                        'warning' => 'villa',
                        'info' => 'studio',
                        'secondary' => 'condo',
                    ]),
                Tables\Columns\TextColumn::make('city')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('price_per_night')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('max_guests')
                    ->label('Guests')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('bedrooms')
                    ->label('Beds')
                    ->alignCenter(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'active',
                        'danger' => 'inactive',
                        'warning' => 'maintenance',
                    ]),
                Tables\Columns\IconColumn::make('featured')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-star')
                    ->trueColor('warning')
                    ->falseColor('gray'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'apartment' => 'Apartment',
                        'house' => 'House',
                        'villa' => 'Villa',
                        'studio' => 'Studio',
                        'condo' => 'Condo',
                        'townhouse' => 'Townhouse',
                        'penthouse' => 'Penthouse',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'maintenance' => 'Under Maintenance',
                    ]),
                Tables\Filters\TernaryFilter::make('featured')
                    ->label('Featured Properties')
                    ->placeholder('All Properties')
                    ->trueLabel('Featured Only')
                    ->falseLabel('Non-Featured Only'),
                Tables\Filters\SelectFilter::make('city')
                    ->options(
                        Property::query()
                            ->distinct()
                            ->pluck('city', 'city')
                            ->toArray()
                    ),
                Tables\Filters\Filter::make('price_range')
                    ->form([
                        Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('price_from')
                                    ->numeric()
                                    ->placeholder('Min Price'),
                                Forms\Components\TextInput::make('price_to')
                                    ->numeric()
                                    ->placeholder('Max Price'),
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
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['price_from'] ?? null) {
                            $indicators['price_from'] = 'Min price: $' . number_format($data['price_from'], 2);
                        }
                        if ($data['price_to'] ?? null) {
                            $indicators['price_to'] = 'Max price: $' . number_format($data['price_to'], 2);
                        }
                        return $indicators;
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
                    Tables\Actions\BulkAction::make('feature')
                        ->label('Mark as Featured')
                        ->icon('heroicon-o-star')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update(['featured' => true]);
                            });
                        })
                        ->requiresConfirmation()
                        ->color('warning'),
                    Tables\Actions\BulkAction::make('unfeature')
                        ->label('Remove Featured')
                        ->icon('heroicon-o-x-mark')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update(['featured' => false]);
                            });
                        })
                        ->requiresConfirmation()
                        ->color('gray'),
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
            'index' => Pages\ListProperties::route('/'),
            'create' => Pages\CreateProperty::route('/create'),
            'view' => Pages\ViewProperty::route('/{record}'),
            'edit' => Pages\EditProperty::route('/{record}/edit'),
        ];
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with(['host']);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'city', 'country', 'host.name'];
    }

    public static function getGlobalSearchResultDetails($record): array
    {
        return [
            'Host' => $record->host?->name,
            'City' => $record->city,
            'Price' => '$' . number_format($record->price_per_night, 2) . '/night',
        ];
    }
}
