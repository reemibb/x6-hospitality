<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BookingResource\Pages;
use App\Filament\Resources\BookingResource\RelationManagers;
use App\Models\Availability; 
use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
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

class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static ?string $navigationGroup = 'Booking Management';
    protected static ?string $navigationLabel = 'Bookings';
    protected static ?string $modelLabel = 'Booking';
    protected static ?string $pluralModelLabel = 'Bookings';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Booking Information')
                    ->description('Create or manage room bookings')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Guest')
                            ->options(function () {
                                return User::where('role', 'guest')
                                    ->orderBy('name')
                                    ->get()
                                    ->mapWithKeys(function ($user) {
                                        return [
                                            $user->id => $user->name . ' (' . $user->email . ')'
                                        ];
                                    });
                            })
                            ->required()
                            ->searchable()
                            ->preload()
                            ->placeholder('Select a guest...')
                            ->helperText('Only guests are available for booking')
                            ->columnSpanFull(),

                        Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('check_in_date')
                                    ->label('Check-in Date')
                                    ->required()
                                    ->native(false)
                                    ->displayFormat('M j, Y')
                                    ->minDate(now()->startOfDay())
                                    ->default(now()->startOfDay())
                                    ->reactive()
                                    ->afterStateUpdated(function (callable $set, $state) {
                                        if ($state) {
                                            $set('check_out_date', null);
                                            $set('room_id', null); 
                                        }
                                    })
                                    ->helperText('Check-in date (today: ' . now()->format('M j, Y') . ')'),

                                Forms\Components\DatePicker::make('check_out_date')
                                    ->label('Check-out Date')
                                    ->required()
                                    ->native(false)
                                    ->displayFormat('M j, Y')
                                    ->minDate(function (callable $get) {
                                        $checkIn = $get('check_in_date');
                                        return $checkIn ? Carbon::parse($checkIn)->addDay() : now()->addDay();
                                    })
                                    ->reactive()
                                    ->afterStateUpdated(function (callable $set, $state) {
                                        $set('room_id', null); 
                                    })
                                    ->helperText('Check-out date (must be after check-in)'),
                            ]),

                        Forms\Components\Select::make('room_id')
                            ->label('Available Room')
                            ->options(function (callable $get) {
                                $checkIn = $get('check_in_date');
                                $checkOut = $get('check_out_date');
                                
                                if (!$checkIn || !$checkOut) {
                                    return [];
                                }
                                
                                $checkInDate = Carbon::parse($checkIn);
                                $checkOutDate = Carbon::parse($checkOut);
                                
                                $availableRoomIds = Availability::where(function ($query) use ($checkInDate, $checkOutDate) {
                                    $query->where('start_date', '<=', $checkInDate)
                                          ->where('end_date', '>=', $checkOutDate);
                                })->pluck('room_id')->unique();
                                
                                $bookedRoomIds = Booking::where('status', '!=', 'cancelled')
                                    ->where(function ($query) use ($checkInDate, $checkOutDate) {
                                        $query->whereBetween('check_in_date', [$checkInDate, $checkOutDate->subDay()])
                                              ->orWhereBetween('check_out_date', [$checkInDate->addDay(), $checkOutDate])
                                              ->orWhere(function ($q) use ($checkInDate, $checkOutDate) {
                                                  $q->where('check_in_date', '<=', $checkInDate)
                                                    ->where('check_out_date', '>=', $checkOutDate);
                                              });
                                    })->pluck('room_id');
                                
                                $finalAvailableRoomIds = $availableRoomIds->diff($bookedRoomIds);
                                
                                return Room::whereIn('room_id', $finalAvailableRoomIds)
                                    ->with('property')
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
                            ->placeholder('Select check-in and check-out dates first...')
                            ->helperText('Only rooms available for your selected dates are shown')
                            ->reactive()
                            ->columnSpanFull(),

                        Forms\Components\Placeholder::make('availability_info')
                            ->label('Room Availability Information')
                            ->content(function (callable $get) {
                                $checkIn = $get('check_in_date');
                                $checkOut = $get('check_out_date');
                                
                                if (!$checkIn || !$checkOut) {
                                    return new \Illuminate\Support\HtmlString("
                                        <div class='text-sm text-gray-500 dark:text-gray-400 italic p-3 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700'>
                                            📅 Select check-in and check-out dates to see available rooms
                                        </div>
                                    ");
                                }
                                
                                $checkInDate = Carbon::parse($checkIn);
                                $checkOutDate = Carbon::parse($checkOut);
                                
                                $availableRoomIds = Availability::where(function ($query) use ($checkInDate, $checkOutDate) {
                                    $query->where('start_date', '<=', $checkInDate)
                                          ->where('end_date', '>=', $checkOutDate);
                                })->pluck('room_id')->unique();
                                
                                $bookedRoomIds = Booking::where('status', '!=', 'cancelled')
                                    ->where(function ($query) use ($checkInDate, $checkOutDate) {
                                        $query->whereBetween('check_in_date', [$checkInDate, $checkOutDate->copy()->subDay()])
                                              ->orWhereBetween('check_out_date', [$checkInDate->copy()->addDay(), $checkOutDate])
                                              ->orWhere(function ($q) use ($checkInDate, $checkOutDate) {
                                                  $q->where('check_in_date', '<=', $checkInDate)
                                                    ->where('check_out_date', '>=', $checkOutDate);
                                              });
                                    })->pluck('room_id');
                                
                                $finalAvailableCount = $availableRoomIds->diff($bookedRoomIds)->count();
                                
                                return new \Illuminate\Support\HtmlString("
                                    <div class='space-y-2 text-sm bg-green-50 dark:bg-green-900/20 p-3 rounded-lg border border-green-200 dark:border-green-800'>
                                        <div class='flex items-center gap-2'>
                                            <svg class='w-4 h-4 text-green-600' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                                                <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M5 13l4 4L19 7'></path>
                                            </svg>
                                            <strong class='text-green-800 dark:text-green-200'>Available Rooms:</strong> 
                                            <span class='text-green-700 dark:text-green-300'>{$finalAvailableCount} room" . ($finalAvailableCount !== 1 ? 's' : '') . " available</span>
                                        </div>
                                        <div class='flex items-center gap-2'>
                                            <svg class='w-4 h-4 text-blue-600' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                                                <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'></path>
                                            </svg>
                                            <strong class='text-blue-800 dark:text-blue-200'>Period:</strong> 
                                            <span class='text-blue-700 dark:text-blue-300'>" . $checkInDate->format('M j') . " - " . $checkOutDate->format('M j, Y') . "</span>
                                        </div>
                                    </div>
                                ");
                            })
                            ->columnSpanFull(),

                        Forms\Components\Placeholder::make('room_details')
                            ->label('Selected Room Details')
                            ->content(function (callable $get) {
                                $roomId = $get('room_id');
                                if ($roomId) {
                                    $room = Room::with('property')->find($roomId);
                                    if ($room) {
                                        return new \Illuminate\Support\HtmlString("
                                            <div class='space-y-2 text-sm bg-gray-50 dark:bg-gray-800 p-3 rounded-lg border border-gray-200 dark:border-gray-700'>
                                                <div class='grid grid-cols-2 gap-2'>
                                                    <div><strong>Property:</strong> {$room->property->title}</div>
                                                    <div><strong>Room Type:</strong> {$room->room_type}</div>
                                                    <div><strong>Price/Night:</strong> $" . number_format($room->price_per_night, 2) . "</div>
                                                    <div><strong>Room ID:</strong> #{$room->room_id}</div>
                                                </div>
                                            </div>
                                        ");
                                    }
                                }
                                return 'Select a room to see details';
                            })
                            ->columnSpanFull(),

                        Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('guests_count')
                                    ->label('Number of Guests')
                                    ->required()
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(10)
                                    ->default(1),

                                Forms\Components\Select::make('status')
                                    ->options([
                                        'pending' => 'Pending',
                                        'confirmed' => 'Confirmed',
                                        'cancelled' => 'Cancelled',
                                    ])
                                    ->required()
                                    ->default('pending')
                                    ->reactive(),
                            ]),

                        Forms\Components\TextInput::make('total_price')
                            ->label('Total Price')
                            ->numeric()
                            ->prefix('$')
                            ->step(0.01)
                            ->reactive()
                            ->afterStateHydrated(function (callable $set, callable $get, $state) {
                                if (!$state) {
                                    $checkIn = $get('check_in_date');
                                    $checkOut = $get('check_out_date');
                                    $roomId = $get('room_id');
                                    
                                    if ($checkIn && $checkOut && $roomId) {
                                        $room = Room::find($roomId);
                                        if ($room) {
                                            $days = Carbon::parse($checkIn)->diffInDays(Carbon::parse($checkOut));
                                            $total = $days * $room->price_per_night;
                                            $set('total_price', $total);
                                        }
                                    }
                                }
                            }),

                        Forms\Components\Placeholder::make('booking_summary')
                            ->label('Booking Summary')
                            ->content(function (callable $get) {
                                $checkIn = $get('check_in_date');
                                $checkOut = $get('check_out_date');
                                $roomId = $get('room_id');
                                $guests = $get('guests_count') ?? 1;
                                $status = $get('status');
                                
                                if ($checkIn && $checkOut && $roomId) {
                                    $room = Room::find($roomId);
                                    $days = Carbon::parse($checkIn)->diffInDays(Carbon::parse($checkOut));
                                    $totalPrice = $room ? $days * $room->price_per_night : 0;
                                    
                                    return new \Illuminate\Support\HtmlString("
                                        <div class='space-y-2 text-sm bg-blue-50 dark:bg-blue-900/20 p-3 rounded-lg border border-blue-200 dark:border-blue-800'>
                                            <div class='grid grid-cols-2 gap-2'>
                                                <div><strong>Duration:</strong> {$days} night" . ($days > 1 ? 's' : '') . "</div>
                                                <div><strong>Guests:</strong> {$guests}</div>
                                                <div><strong>Rate/Night:</strong> $" . number_format($room->price_per_night ?? 0, 2) . "</div>
                                                <div><strong>Total Cost:</strong> $" . number_format($totalPrice, 2) . "</div>
                                                <div><strong>Status:</strong> <span class='capitalize'>{$status}</span></div>
                                                <div><strong>Check-in:</strong> " . Carbon::parse($checkIn)->format('M j, Y') . "</div>
                                            </div>
                                        </div>
                                    ");
                                }
                                
                                return 'Complete booking details to see summary';
                            })
                            ->columnSpanFull(),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('booking_reference')
                    ->label('Booking Ref')
                    ->getStateUsing(function ($record) {
                        return 'BK-' . str_pad($record->booking_id, 6, '0', STR_PAD_LEFT);
                    })
                    ->badge()
                    ->color('info')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('booking_id', 'like', "%{$search}%");
                    }),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Guest')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->user->email),

                Tables\Columns\TextColumn::make('room.property.title')
                    ->label('Property')
                    ->searchable()
                    ->sortable()
                    ->limit(20),

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

                Tables\Columns\TextColumn::make('check_in_date')
                    ->label('Check-in')
                    ->date('M j, Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('check_out_date')
                    ->label('Check-out')
                    ->date('M j, Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('duration')
                    ->label('Nights')
                    ->getStateUsing(function ($record) {
                        return $record->check_in_date->diffInDays($record->check_out_date);
                    })
                    ->alignCenter()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('guests_count')
                    ->label('Guests')
                    ->alignCenter(),

                Tables\Columns\BadgeColumn::make('booking_status')
                    ->label('Status')
                    ->getStateUsing(function ($record) {
                        $now = Carbon::now()->startOfDay();
                        
                        if ($record->status === 'cancelled') {
                            return 'Cancelled';
                        } elseif ($record->status === 'pending') {
                            return 'Pending';
                        } elseif ($record->check_out_date < $now) {
                            return 'Completed';
                        } elseif ($record->check_in_date <= $now && $record->check_out_date > $now) {
                            return 'Active';
                        } else {
                            return 'Upcoming';
                        }
                    })
                    ->colors([
                        'success' => 'Active',
                        'info' => 'Upcoming',
                        'warning' => 'Pending',
                        'danger' => 'Cancelled',
                        'gray' => 'Completed',
                    ])
                    ->icons([
                        'heroicon-o-check-circle' => 'Active',
                        'heroicon-o-clock' => 'Upcoming',
                        'heroicon-o-exclamation-triangle' => 'Pending',
                        'heroicon-o-x-circle' => 'Cancelled',
                        'heroicon-o-check' => 'Completed',
                    ]),

                Tables\Columns\TextColumn::make('total_price')
                    ->label('Total Price')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Booked On')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'cancelled' => 'Cancelled',
                    ]),

                Tables\Filters\SelectFilter::make('booking_status')
                    ->label('Booking Status')
                    ->options([
                        'active' => 'Active',
                        'upcoming' => 'Upcoming',
                        'pending' => 'Pending',
                        'cancelled' => 'Cancelled',
                        'completed' => 'Completed',
                    ])
                    ->query(function (Builder $query, array $data) {
                        $status = $data['value'] ?? null;
                        $now = Carbon::now()->startOfDay();
                        
                        return match ($status) {
                            'active' => $query->where('check_in_date', '<=', $now)
                                             ->where('check_out_date', '>', $now)
                                             ->where('status', 'confirmed'),
                            'upcoming' => $query->where('check_in_date', '>', $now)
                                                ->where('status', 'confirmed'),
                            'pending' => $query->where('status', 'pending'),
                            'cancelled' => $query->where('status', 'cancelled'),
                            'completed' => $query->where('check_out_date', '<', $now)
                                                 ->where('status', 'confirmed'),
                            default => $query,
                        };
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('confirm')
                    ->label('Confirm')
                    ->icon('heroicon-o-check-circle')
                    ->action(function (Booking $record) {
                        $record->update(['status' => 'confirmed']);
                    })
                    ->visible(fn (Booking $record) => $record->status === 'pending')
                    ->color('success')
                    ->requiresConfirmation(),
                    
                Tables\Actions\Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-circle')
                    ->action(function (Booking $record) {
                        $record->update(['status' => 'cancelled']);
                    })
                    ->visible(fn (Booking $record) => $record->status !== 'cancelled')
                    ->color('danger')
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('confirm_bookings')
                        ->label('Confirm Bookings')
                        ->icon('heroicon-o-check-circle')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                if ($record->status === 'pending') {
                                    $record->update(['status' => 'confirmed']);
                                }
                            });
                        })
                        ->requiresConfirmation()
                        ->color('success'),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
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
            'index' => Pages\ListBookings::route('/'),
            'create' => Pages\CreateBooking::route('/create'),
            'edit' => Pages\EditBooking::route('/{record}/edit'),
        ];
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with(['user', 'room.property']);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['user.name', 'user.email', 'room.property.title', 'room.room_type'];
    }

    public static function getGlobalSearchResultDetails($record): array
    {
        return [
            'Guest' => $record->user?->name,
            'Property' => $record->room?->property?->title,
            'Room' => $record->room?->room_type,
            'Check-in' => $record->check_in_date?->format('M j, Y'),
            'Status' => $record->status,
        ];
    }
}
