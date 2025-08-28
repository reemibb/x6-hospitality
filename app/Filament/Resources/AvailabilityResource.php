<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AvailabilityResource\Pages;
use App\Filament\Resources\AvailabilityResource\RelationManagers;
use App\Models\Availability;
use App\Models\Room;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Carbon\Carbon;

class AvailabilityResource extends Resource
{
    protected static ?string $model = Availability::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationGroup = 'Property Management';
    protected static ?string $navigationLabel = 'Room Availability';
    protected static ?string $modelLabel = 'Availability';
    protected static ?string $pluralModelLabel = 'Availabilities';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Availability Information')
                    ->description('Set availability periods for rooms')
                    ->schema([
                        Forms\Components\Select::make('room_id')
                            ->label('Room')
                            ->options(function () {
                                return Room::with('property')
                                    ->get()
                                    ->mapWithKeys(function ($room) {
                                        return [
                                            $room->room_id => 
                                            $room->property->title . ' - ' . 
                                            $room->room_type . ' (ID: ' . 
                                            $room->room_id . ') - $' . 
                                            number_format($room->price_per_night, 2) . '/night'
                                        ];
                                    });
                            })
                            ->required()
                            ->searchable()
                            ->preload()
                            ->columnSpanFull()
                            ->reactive()
                            ->afterStateUpdated(function (callable $set, $state) {
                                if ($state) {
                                    $room = Room::with('property')->find($state);
                                    if ($room) {
                                        $set('room_info', [
                                            'property' => $room->property->title,
                                            'type' => $room->room_type,
                                            'price' => $room->price_per_night
                                        ]);
                                    }
                                }
                            }),
                        
                        Forms\Components\Placeholder::make('room_details')
                        ->label('Selected Room Details')
                        ->content(function (callable $get) {
                            $roomId = $get('room_id');
                            if ($roomId) {
                                $room = Room::with('property')->find($roomId);
                                if ($room) {
                                    return new \Illuminate\Support\HtmlString("
                                        <div class='space-y-3 text-sm bg-gray-50 dark:bg-gray-800 p-4 rounded-lg border border-gray-200 dark:border-gray-700'>
                                            <div class='grid grid-cols-2 gap-3'>
                                                <div class='flex items-center gap-2'>
                                                    <svg class='w-4 h-4 text-blue-600' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                                                        <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'></path>
                                                    </svg>
                                                    <div>
                                                        <strong class='text-gray-700 dark:text-gray-300'>Property:</strong><br>
                                                        <span class='text-gray-600 dark:text-gray-400'>" . $room->property->title . "</span>
                                                    </div>
                                                </div>
                                                <div class='flex items-center gap-2'>
                                                    <svg class='w-4 h-4 text-purple-600' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                                                        <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z'></path>
                                                    </svg>
                                                    <div>
                                                        <strong class='text-gray-700 dark:text-gray-300'>Room Type:</strong><br>
                                                        <span class='text-gray-600 dark:text-gray-400'>" . $room->room_type . "</span>
                                                    </div>
                                                </div>
                                                <div class='flex items-center gap-2'>
                                                    <svg class='w-4 h-4 text-green-600' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                                                        <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1'></path>
                                                    </svg>
                                                    <div>
                                                        <strong class='text-gray-700 dark:text-gray-300'>Price/Night:</strong><br>
                                                        <span class='text-green-600 dark:text-green-400 font-semibold'>$" . number_format($room->price_per_night, 2) . "</span>
                                                    </div>
                                                </div>
                                                <div class='flex items-center gap-2'>
                                                    <svg class='w-4 h-4 text-orange-600' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                                                        <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z'></path>
                                                    </svg>
                                                    <div>
                                                        <strong class='text-gray-700 dark:text-gray-300'>Room ID:</strong><br>
                                                        <span class='text-gray-600 dark:text-gray-400'>#" . $room->room_id . "</span>
                                                    </div>
                                                </div>
                                            </div>
                                            " . ($room->description ? "
                                            <div class='border-t border-gray-200 dark:border-gray-600 pt-3'>
                                                <strong class='text-gray-700 dark:text-gray-300'>Description:</strong><br>
                                                <span class='text-gray-600 dark:text-gray-400'>" . \Illuminate\Support\Str::limit($room->description, 150) . "</span>
                                            </div>
                                            " : "") . "
                                        </div>
                                    ");
                                }
                            }
                            return new \Illuminate\Support\HtmlString("
                                <div class='text-sm text-gray-500 dark:text-gray-400 italic p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 text-center'>
                                    🏨 Select a room to see detailed information
                                </div>
                            ");
                        })
                        ->columnSpanFull(),
                        
                        Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('start_date')
                                    ->label('Start Date')
                                    ->required()
                                    ->native(false)
                                    ->displayFormat('M j, Y')
                                    ->reactive()
                                    ->afterStateUpdated(function (callable $set, $state) {
                                        if ($state) {
                                            $set('end_date', null);
                                        }
                                    })
                                    ->helperText('Room becomes available from this date'),

                                Forms\Components\DatePicker::make('end_date')
                                    ->label('End Date')
                                    ->required()
                                    ->native(false)
                                    ->displayFormat('M j, Y')
                                    ->minDate(function (callable $get) {
                                        $startDate = $get('start_date');
                                        return $startDate ? Carbon::parse($startDate) : null;
                                    })
                                    ->reactive()
                                    ->helperText('Room available until this date (must be same or after start date)'),
                            ]),
                        
                        Forms\Components\Placeholder::make('duration_info')
                        ->label('Duration & Revenue Information')
                        ->content(function (callable $get) {
                            $startDate = $get('start_date');
                            $endDate = $get('end_date');
                            $roomId = $get('room_id');
                            
                            if ($startDate && $endDate && $roomId) {
                                $start = Carbon::parse($startDate);
                                $end = Carbon::parse($endDate);
                                $days = $start->diffInDays($end) + 1;
                                
                                $room = Room::find($roomId);
                                $totalRevenue = $room ? $days * $room->price_per_night : 0;
                                
                                return new \Illuminate\Support\HtmlString("
                                    <div class='space-y-2 text-sm bg-blue-50 dark:bg-blue-900/20 p-3 rounded-lg border border-blue-200 dark:border-blue-800'>
                                        <div class='flex items-center gap-2'>
                                            <svg class='w-4 h-4 text-blue-600' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                                                <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'></path>
                                            </svg>
                                            <strong class='text-blue-800 dark:text-blue-200'>Duration:</strong> 
                                            <span class='text-blue-700 dark:text-blue-300'>{$days} day" . ($days > 1 ? 's' : '') . "</span>
                                        </div>
                                        <div class='flex items-center gap-2'>
                                            <svg class='w-4 h-4 text-green-600' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                                                <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1'></path>
                                            </svg>
                                            <strong class='text-green-800 dark:text-green-200'>Potential Revenue:</strong> 
                                            <span class='text-green-700 dark:text-green-300 font-semibold'>$" . number_format($totalRevenue, 2) . "</span>
                                        </div>
                                    </div>
                                ");
                            }
                            
                            return new \Illuminate\Support\HtmlString("
                                <div class='text-sm text-gray-500 dark:text-gray-400 italic p-3 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700'>
                                    📅 Select dates and room to see duration and revenue information
                                </div>
                            ");
                        })
                        ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('room.photos')
                    ->label('Room')
                    ->getStateUsing(function ($record) {
                        $photos = $record->room->photos ?? [];
                        return is_array($photos) && count($photos) > 0 ? $photos[0] : null;
                    })
                    ->circular()
                    ->size(40)
                    ->defaultImageUrl(url('/images/placeholder-room.png')),
                
                Tables\Columns\TextColumn::make('room.property.title')
                    ->label('Property')
                    ->searchable()
                    ->sortable()
                    ->limit(25)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 25 ? $state : null;
                    }),
                
                Tables\Columns\TextColumn::make('room.room_type')
                    ->label('Room Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Suite' => 'success',
                        'Presidential Suite' => 'danger',
                        'Deluxe Room' => 'warning',
                        'Studio' => 'info',
                        default => 'gray',
                    }),
                
                Tables\Columns\TextColumn::make('room_id')
                    ->label('Room ID')
                    ->alignCenter()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Start Date')
                    ->date('M j, Y')
                    ->sortable()
                    ->color('success'),
                
                Tables\Columns\TextColumn::make('end_date')
                    ->label('End Date')
                    ->date('M j, Y')
                    ->sortable()
                    ->color('danger'),
                
                Tables\Columns\TextColumn::make('duration')
                    ->label('Duration')
                    ->getStateUsing(function ($record) {
                        $days = $record->start_date->diffInDays($record->end_date) + 1;
                        return $days . ' day' . ($days > 1 ? 's' : '');
                    })
                    ->alignCenter()
                    ->badge()
                    ->color('info'),
                
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->getStateUsing(function ($record) {
                        $now = Carbon::now()->startOfDay();
                        
                        if ($record->end_date < $now) {
                            return 'Expired';
                        } elseif ($record->start_date <= $now && $record->end_date >= $now) {
                            return 'Active';
                        } else {
                            return 'Upcoming';
                        }
                    })
                    ->colors([
                        'success' => 'Active',
                        'warning' => 'Upcoming',
                        'danger' => 'Expired',
                    ])
                    ->icons([
                        'heroicon-o-check-circle' => 'Active',
                        'heroicon-o-clock' => 'Upcoming',
                        'heroicon-o-x-circle' => 'Expired',
                    ]),
                
                Tables\Columns\TextColumn::make('room.price_per_night')
                    ->label('Price/Night')
                    ->money('USD')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('potential_revenue')
                    ->label('Potential Revenue')
                    ->getStateUsing(function ($record) {
                        $days = $record->start_date->diffInDays($record->end_date) + 1;
                        return $days * $record->room->price_per_night;
                    })
                    ->money('USD')
                    ->color('success')
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('room_id')
                    ->label('Room')
                    ->options(function () {
                        return Room::with('property')
                            ->get()
                            ->mapWithKeys(function ($room) {
                                return [
                                    $room->room_id => 
                                    $room->property->title . ' - ' . 
                                    $room->room_type . ' (ID: ' . 
                                    $room->room_id . ')'
                                ];
                            });
                    })
                    ->searchable()
                    ->preload(),
                
                Tables\Filters\SelectFilter::make('property')
                    ->label('Property')
                    ->options(function () {
                        return Room::with('property')
                            ->get()
                            ->pluck('property.title', 'property.property_id')
                            ->unique();
                    })
                    ->query(function (Builder $query, array $data) {
                        if ($data['value']) {
                            $query->whereHas('room.property', function ($q) use ($data) {
                                $q->where('property_id', $data['value']);
                            });
                        }
                    }),
                
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
                    ->query(function (Builder $query, array $data) {
                        if ($data['value']) {
                            $query->whereHas('room', function ($q) use ($data) {
                                $q->where('room_type', $data['value']);
                            });
                        }
                    }),
                
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'active' => 'Active',
                        'upcoming' => 'Upcoming',
                        'expired' => 'Expired',
                    ])
                    ->query(function (Builder $query, array $data) {
                        $status = $data['value'] ?? null;
                        $now = Carbon::now()->startOfDay();
                        
                        return match ($status) {
                            'active' => $query->where('start_date', '<=', $now)->where('end_date', '>=', $now),
                            'upcoming' => $query->where('start_date', '>', $now),
                            'expired' => $query->where('end_date', '<', $now),
                            default => $query,
                        };
                    }),
                
                Tables\Filters\Filter::make('date_range')
                    ->label('Date Range')
                    ->form([
                        Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('date_from')
                                    ->label('From Date')
                                    ->placeholder('Start date')
                                    ->native(false),
                                Forms\Components\DatePicker::make('date_to')
                                    ->label('To Date')
                                    ->placeholder('End date')
                                    ->native(false),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['date_from'],
                                fn (Builder $query, $date): Builder => $query->where('start_date', '>=', $date),
                            )
                            ->when(
                                $data['date_to'],
                                fn (Builder $query, $date): Builder => $query->where('end_date', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['date_from'] ?? null) {
                            $indicators['date_from'] = 'From: ' . Carbon::parse($data['date_from'])->format('M j, Y');
                        }
                        if ($data['date_to'] ?? null) {
                            $indicators['date_to'] = 'To: ' . Carbon::parse($data['date_to'])->format('M j, Y');
                        }
                        return $indicators;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->color('warning'),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('duplicate')
                    ->label('Duplicate')
                    ->icon('heroicon-o-document-duplicate')
                    ->action(function (Availability $record) {
                        $newRecord = $record->replicate();
                        $newRecord->start_date = $record->end_date->addDay();
                        $newRecord->end_date = $record->end_date->addDays(7);
                        $newRecord->save();
                    })
                    ->requiresConfirmation()
                    ->color('info')
                    ->successNotificationTitle('Availability duplicated successfully'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    Tables\Actions\BulkAction::make('extend_availability')
                        ->label('Extend Availability')
                        ->icon('heroicon-o-plus-circle')
                        ->form([
                            Forms\Components\DatePicker::make('extend_to')
                                ->label('Extend End Date To')
                                ->required()
                                ->minDate(now())
                                ->native(false)
                                ->helperText('All selected availabilities will be extended to this date'),
                        ])
                        ->action(function ($records, array $data) {
                            $records->each(function ($record) use ($data) {
                                $record->update(['end_date' => $data['extend_to']]);
                            });
                        })
                        ->requiresConfirmation()
                        ->color('success')
                        ->successNotificationTitle('Availabilities extended successfully'),
                    
                    Tables\Actions\BulkAction::make('mark_expired')
                        ->label('Mark as Expired')
                        ->icon('heroicon-o-x-circle')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update(['end_date' => now()->subDay()]);
                            });
                        })
                        ->requiresConfirmation()
                        ->color('danger')
                        ->successNotificationTitle('Availabilities marked as expired'),
                ]),
            ])
            ->defaultSort('start_date', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100]);
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
            'index' => Pages\ListAvailabilities::route('/'),
            'create' => Pages\CreateAvailability::route('/create'),
            'edit' => Pages\EditAvailability::route('/{record}/edit'),
        ];
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with(['room.property']);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['room.property.title', 'room.room_type'];
    }

    public static function getGlobalSearchResultDetails($record): array
    {
        return [
            'Property' => $record->room?->property?->title,
            'Room Type' => $record->room?->room_type,
            'Duration' => $record->duration . ' days',
            'Status' => $record->status,
        ];
    }
}
