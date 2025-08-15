<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AmenityResource\Pages;
use App\Filament\Resources\AmenityResource\RelationManagers;
use App\Models\Amenity;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AmenityResource extends Resource
{
    protected static ?string $model = Amenity::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Property Management';
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Amenity Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->columnSpan(2),

                        Forms\Components\Select::make('category')
                            ->label('Category')
                            ->options([
                                'property' => 'Property Only',
                                'room' => 'Room Only',
                                'both' => 'Both Property & Room',
                            ])
                            ->required()
                            ->default('both')
                            ->helperText('Where this amenity can be used'),

                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->placeholder('Describe this amenity...')
                            ->columnSpanFull(),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('category')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'property' => 'info',
                        'room' => 'warning',
                        'both' => 'success',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'property' => 'Property',
                        'room' => 'Room',
                        'both' => 'Both',
                    }),

                Tables\Columns\TextColumn::make('description')
                    ->limit(50)
                    ->placeholder('No description')
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 50 ? $state : null;
                    }),

                Tables\Columns\TextColumn::make('properties_count')
                    ->label('Used in Properties')
                    ->counts('properties')
                    ->badge()
                    ->color('info')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('rooms_count')
                    ->label('Used in Rooms')
                    ->counts('rooms')
                    ->badge()
                    ->color('warning')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->options([
                        'property' => 'Property Only',
                        'room' => 'Room Only',
                        'both' => 'Both Property & Room',
                    ])
                    ->multiple(),
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
            ->defaultSort('name');
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
            'index' => Pages\ListAmenities::route('/'),
            'create' => Pages\CreateAmenity::route('/create'),
            'view' => Pages\ViewAmenity::route('/{record}'),
            'edit' => Pages\EditAmenity::route('/{record}/edit'),
        ];
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with(['properties']);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'description'];
    }

    public static function getGlobalSearchResultDetails($record): array
    {
        return [
            'Description' => $record->description ? substr($record->description, 0, 50) . '...' : 'No description',
            'Used in Properties' => $record->properties()->count() . ' properties',
        ];
    }
}
