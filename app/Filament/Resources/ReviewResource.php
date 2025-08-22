<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReviewResource\Pages;
use App\Filament\Resources\ReviewResource\RelationManagers;
use App\Models\Review;
use App\Models\User;
use App\Models\Property;
use App\Models\Booking;
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

class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static ?string $navigationIcon = 'heroicon-o-star';
    protected static ?string $navigationGroup = 'Customer Relations';
    protected static ?string $navigationLabel = 'Reviews';
    protected static ?string $modelLabel = 'Review';
    protected static ?string $pluralModelLabel = 'Reviews';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Review Information')
                    ->description('Manage customer reviews and ratings')
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
                            ->reactive()
                            ->afterStateUpdated(function (callable $set, $state) {
                                if ($state) {
                                    $set('booking_id', null);
                                    $set('property_id', null);
                                }
                            }),

                        Forms\Components\Select::make('booking_id')
                            ->label('Booking')
                            ->options(function (callable $get) {
                                $userId = $get('user_id');
                                if (!$userId) {
                                    return [];
                                }
                                
                                return Booking::where('user_id', $userId)
                                    ->with(['room.property'])
                                    ->get()
                                    ->mapWithKeys(function ($booking) {
                                        return [
                                            $booking->booking_id => 
                                            'BK-' . str_pad($booking->booking_id, 6, '0', STR_PAD_LEFT) . 
                                            ' - ' . $booking->room->property->title . 
                                            ' (' . $booking->check_in_date->format('M j, Y') . ')'
                                        ];
                                    });
                            })
                            ->required()
                            ->searchable()
                            ->reactive()
                            ->afterStateUpdated(function (callable $set, $state) {
                                if ($state) {
                                    $booking = Booking::with('room.property')->find($state);
                                    if ($booking) {
                                        $set('property_id', $booking->room->property->property_id);
                                    }
                                }
                            })
                            ->placeholder('Select guest first...'),

                        Forms\Components\Select::make('property_id')
                            ->label('Property')
                            ->options(Property::pluck('title', 'property_id'))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->disabled()
                            ->dehydrated(),

                        Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('rating')
                                    ->label('Rating')
                                    ->options([
                                        5 => '5 ⭐ - Excellent',
                                        4 => '4 ⭐ - Very Good',
                                        3 => '3 ⭐ - Good',
                                        2 => '2 ⭐ - Fair',
                                        1 => '1 ⭐ - Poor',
                                    ])
                                    ->required()
                                    ->reactive(),

                                Forms\Components\Select::make('status')
                                    ->label('Status')
                                    ->options([
                                        'published' => 'Published',
                                        'pending' => 'Pending Review',
                                        'flagged' => 'Flagged',
                                        'hidden' => 'Hidden',
                                    ])
                                    ->required()
                                    ->default('published')
                                    ->reactive(),

                                Forms\Components\Toggle::make('verified')
                                    ->label('Verified Guest')
                                    ->helperText('Mark as verified guest review')
                                    ->default(function (callable $get) {
                                        $bookingId = $get('booking_id');
                                        if ($bookingId) {
                                            $booking = Booking::find($bookingId);
                                            return $booking && 
                                                   $booking->status === 'confirmed' && 
                                                   $booking->check_out_date < now();
                                        }
                                        return false;
                                    }),
                            ]),

                        Forms\Components\Textarea::make('comment')
                            ->label('Review Comment')
                            ->required()
                            ->rows(4)
                            ->maxLength(2000)
                            ->columnSpanFull()
                            ->reactive()
                            ->live()
                            ->afterStateUpdated(function (callable $set, $state) {
                                $wordCount = $state ? str_word_count(strip_tags($state)) : 0;
                                $set('word_count', $wordCount);
                            }),

                        Forms\Components\Placeholder::make('word_count_display')
                            ->label('Word Count')
                            ->content(function (callable $get) {
                                $comment = $get('comment');
                                $wordCount = $comment ? str_word_count(strip_tags($comment)) : 0;
                                return $wordCount . ' words';
                            }),

                        Forms\Components\TextInput::make('helpful_votes')
                            ->label('Helpful Votes')
                            ->numeric()
                            ->default(0)
                            ->minValue(0),

                        Forms\Components\Placeholder::make('booking_details')
                            ->label('Booking Details')
                            ->content(function (callable $get) {
                                $bookingId = $get('booking_id');
                                if ($bookingId) {
                                    $booking = Booking::with(['user', 'room.property'])->find($bookingId);
                                    if ($booking) {
                                        $canReview = $booking->status === 'confirmed' && $booking->check_out_date < now();
                                        $statusColor = $canReview ? 'green' : 'orange';
                                        $statusText = $canReview ? 'Eligible for review' : 'Booking not completed yet';
                                        
                                        return new \Illuminate\Support\HtmlString("
                                            <div class='space-y-2 text-sm bg-blue-50 dark:bg-blue-900/20 p-3 rounded-lg border border-blue-200 dark:border-blue-800'>
                                                <div class='grid grid-cols-2 gap-2'>
                                                    <div><strong>Guest:</strong> {$booking->user->name}</div>
                                                    <div><strong>Property:</strong> {$booking->room->property->title}</div>
                                                    <div><strong>Check-in:</strong> " . $booking->check_in_date->format('M j, Y') . "</div>
                                                    <div><strong>Check-out:</strong> " . $booking->check_out_date->format('M j, Y') . "</div>
                                                    <div><strong>Status:</strong> <span class='capitalize'>{$booking->status}</span></div>
                                                    <div><strong>Review Status:</strong> <span class='text-{$statusColor}-600'>{$statusText}</span></div>
                                                </div>
                                            </div>
                                        ");
                                    }
                                }
                                return 'Select a booking to see details';
                            })
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Host Response')
                    ->description('Respond to customer reviews')
                    ->schema([
                        Forms\Components\Textarea::make('response')
                            ->label('Response to Review')
                            ->rows(3)
                            ->maxLength(1000)
                            ->placeholder('Write a professional response to this review...')
                            ->columnSpanFull()
                            ->helperText('This response will be visible to guests and the public'),

                        Forms\Components\DateTimePicker::make('responded_at')
                            ->label('Response Date')
                            ->native(false)
                            ->displayFormat('M j, Y g:i A')
                            ->default(function (callable $get) {
                                return $get('response') ? now() : null;
                            }),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Section::make('Review Summary')
                    ->schema([
                        Forms\Components\Placeholder::make('review_summary')
                            ->label('')
                            ->content(function (callable $get) {
                                $rating = $get('rating');
                                $status = $get('status');
                                $comment = $get('comment');
                                $response = $get('response');
                                $verified = $get('verified');
                                
                                if ($rating && $status && $comment) {
                                    $stars = str_repeat('⭐', $rating);
                                    $ratingText = match($rating) {
                                        5 => 'Excellent',
                                        4 => 'Very Good',
                                        3 => 'Good',
                                        2 => 'Fair',
                                        1 => 'Poor',
                                        default => 'No Rating',
                                    };
                                    
                                    $statusColor = match($status) {
                                        'published' => 'green',
                                        'pending' => 'yellow',
                                        'flagged' => 'red',
                                        'hidden' => 'gray',
                                        default => 'blue',
                                    };
                                    
                                    $wordCount = str_word_count(strip_tags($comment));
                                    $verifiedBadge = $verified ? '<span class="inline-flex items-center px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">✓ Verified</span>' : '';
                                    $responseBadge = $response ? '<span class="inline-flex items-center px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">💬 Response Added</span>' : '';
                                    
                                    return new \Illuminate\Support\HtmlString("
                                        <div class='space-y-3 text-sm bg-gray-50 dark:bg-gray-800 p-4 rounded-lg border border-gray-200 dark:border-gray-700'>
                                            <div class='flex items-center gap-3'>
                                                <div class='text-lg'>{$stars}</div>
                                                <div><strong>{$ratingText}</strong></div>
                                                <span class='inline-flex items-center px-2 py-1 text-xs font-medium bg-{$statusColor}-100 text-{$statusColor}-800 rounded-full capitalize'>{$status}</span>
                                                {$verifiedBadge}
                                                {$responseBadge}
                                            </div>
                                            <div class='bg-white dark:bg-gray-900 p-3 rounded border italic'>
                                                \"{$comment}\"
                                            </div>
                                            <div class='text-xs text-gray-500'>
                                                Word count: {$wordCount} words
                                            </div>
                                        </div>
                                    ");
                                }
                                
                                return 'Complete review details to see summary';
                            })
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\BadgeColumn::make('rating')
                    ->label('Rating')
                    ->formatStateUsing(fn ($state) => $state . '⭐')
                    ->colors([
                        'danger' => fn ($state) => $state <= 2,
                        'warning' => 3,
                        'info' => 4,
                        'success' => 5,
                    ])
                    ->sortable(),

                Tables\Columns\TextColumn::make('guest.name')
                    ->label('Guest')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->guest->email),

                Tables\Columns\TextColumn::make('property.title')
                    ->label('Property')
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->url(fn ($record) => route('filament.admin.resources.properties.edit', $record->property))
                    ->openUrlInNewTab(),

                Tables\Columns\TextColumn::make('booking_reference')
                    ->label('Booking')
                    ->getStateUsing(function ($record) {
                        return $record->booking ? 'BK-' . str_pad($record->booking->booking_id, 6, '0', STR_PAD_LEFT) : 'N/A';
                    })
                    ->badge()
                    ->color('info')
                    ->url(fn ($record) => $record->booking ? route('filament.admin.resources.bookings.edit', $record->booking) : null)
                    ->openUrlInNewTab(),

                Tables\Columns\TextColumn::make('comment')
                    ->label('Review')
                    ->limit(80)
                    ->tooltip(function ($record) {
                        return $record->comment;
                    })
                    ->wrap()
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'published',
                        'warning' => 'pending',
                        'danger' => 'flagged',
                        'gray' => 'hidden',
                    ])
                    ->icons([
                        'heroicon-o-eye' => 'published',
                        'heroicon-o-clock' => 'pending',
                        'heroicon-o-flag' => 'flagged',
                        'heroicon-o-eye-slash' => 'hidden',
                    ]),

                Tables\Columns\IconColumn::make('verified')
                    ->label('Verified')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-mark')
                    ->trueColor('success')
                    ->falseColor('gray'),

                Tables\Columns\IconColumn::make('has_response')
                    ->label('Response')
                    ->getStateUsing(fn ($record) => !empty($record->response))
                    ->boolean()
                    ->trueIcon('heroicon-o-chat-bubble-left-right')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('info')
                    ->falseColor('gray'),

                Tables\Columns\TextColumn::make('helpful_votes')
                    ->label('Helpful')
                    ->badge()
                    ->color(fn ($state) => $state > 5 ? 'success' : ($state > 0 ? 'warning' : 'gray'))
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('word_count')
                    ->label('Words')
                    ->getStateUsing(fn ($record) => $record->comment ? str_word_count(strip_tags($record->comment)) : 0)
                    ->badge()
                    ->color(fn ($state) => $state > 50 ? 'success' : ($state > 20 ? 'warning' : 'gray'))
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Posted')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->description(fn ($record) => $record->created_at->diffForHumans()),

                Tables\Columns\TextColumn::make('responded_at')
                    ->label('Responded')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->placeholder('No response')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('rating')
                    ->options([
                        5 => '5 Stars - Excellent',
                        4 => '4 Stars - Very Good',
                        3 => '3 Stars - Good',
                        2 => '2 Stars - Fair',
                        1 => '1 Star - Poor',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'published' => 'Published',
                        'pending' => 'Pending',
                        'flagged' => 'Flagged',
                        'hidden' => 'Hidden',
                    ])
                    ->multiple(),

                Tables\Filters\TernaryFilter::make('verified')
                    ->label('Verified Guest')
                    ->placeholder('All reviews')
                    ->trueLabel('Verified only')
                    ->falseLabel('Unverified only'),

                Tables\Filters\TernaryFilter::make('has_response')
                    ->label('Has Response')
                    ->placeholder('All reviews')
                    ->trueLabel('With response')
                    ->falseLabel('Without response')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('response'),
                        false: fn (Builder $query) => $query->whereNull('response'),
                    ),

                Tables\Filters\Filter::make('recent')
                    ->label('Recent Reviews')
                    ->query(fn (Builder $query): Builder => $query->where('created_at', '>=', Carbon::now()->subDays(30)))
                    ->toggle(),

                Tables\Filters\Filter::make('high_rated')
                    ->label('High Rated (4-5 stars)')
                    ->query(fn (Builder $query): Builder => $query->where('rating', '>=', 4))
                    ->toggle(),

                Tables\Filters\Filter::make('low_rated')
                    ->label('Low Rated (1-2 stars)')
                    ->query(fn (Builder $query): Builder => $query->where('rating', '<=', 2))
                    ->toggle(),

                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('posted_from')
                                    ->label('Posted From')
                                    ->native(false),
                                Forms\Components\DatePicker::make('posted_to')
                                    ->label('Posted To')
                                    ->native(false),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['posted_from'], fn ($q, $date) => $q->where('created_at', '>=', $date))
                            ->when($data['posted_to'], fn ($q, $date) => $q->where('created_at', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->action(function (Review $record) {
                        $record->update(['status' => 'published']);
                    })
                    ->visible(fn (Review $record) => $record->status === 'pending')
                    ->color('success')
                    ->requiresConfirmation(),

                Tables\Actions\Action::make('flag')
                    ->label('Flag')
                    ->icon('heroicon-o-flag')
                    ->action(function (Review $record) {
                        $record->update(['status' => 'flagged']);
                    })
                    ->visible(fn (Review $record) => $record->status !== 'flagged')
                    ->color('danger')
                    ->requiresConfirmation(),

                Tables\Actions\Action::make('hide')
                    ->label('Hide')
                    ->icon('heroicon-o-eye-slash')
                    ->action(function (Review $record) {
                        $record->update(['status' => 'hidden']);
                    })
                    ->visible(fn (Review $record) => $record->status !== 'hidden')
                    ->color('gray')
                    ->requiresConfirmation(),

                Tables\Actions\Action::make('respond')
                    ->label('Respond')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->form([
                        Forms\Components\Textarea::make('response')
                            ->label('Response')
                            ->required()
                            ->rows(4)
                            ->maxLength(1000)
                            ->placeholder('Write a professional response...'),
                    ])
                    ->action(function (Review $record, array $data) {
                        $record->update([
                            'response' => $data['response'],
                            'responded_at' => now(),
                        ]);
                    })
                    ->visible(fn (Review $record) => empty($record->response))
                    ->color('info'),

                Tables\Actions\Action::make('view_booking')
                    ->label('View Booking')
                    ->icon('heroicon-o-calendar')
                    ->url(fn (Review $record) => $record->booking ? route('filament.admin.resources.bookings.edit', $record->booking) : null)
                    ->visible(fn (Review $record) => $record->booking !== null)
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    Tables\Actions\BulkAction::make('approve_reviews')
                        ->label('Approve Selected')
                        ->icon('heroicon-o-check-circle')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                if ($record->status === 'pending') {
                                    $record->update(['status' => 'published']);
                                }
                            });
                        })
                        ->requiresConfirmation()
                        ->color('success'),

                    Tables\Actions\BulkAction::make('flag_reviews')
                        ->label('Flag Selected')
                        ->icon('heroicon-o-flag')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update(['status' => 'flagged']);
                            });
                        })
                        ->requiresConfirmation()
                        ->color('danger'),

                    Tables\Actions\BulkAction::make('export_reviews')
                        ->label('Export Selected')
                        ->icon('heroicon-o-document-arrow-down')
                        ->action(function ($records) {
                            return redirect()->back()->with('success', 'Export functionality would be implemented here.');
                        })
                        ->requiresConfirmation()
                        ->color('info'),
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
            'index' => Pages\ListReviews::route('/'),
            'create' => Pages\CreateReview::route('/create'),
            'edit' => Pages\EditReview::route('/{record}/edit'),
        ];
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with(['guest', 'property', 'booking']);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['comment', 'guest.name', 'guest.email', 'property.title'];
    }

    public static function getGlobalSearchResultDetails($record): array
    {
        return [
            'Guest' => $record->guest?->name,
            'Property' => $record->property?->title,
            'Rating' => $record->rating . '⭐',
            'Status' => $record->status,
            'Posted' => $record->created_at?->format('M j, Y'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::pending()->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $pendingCount = static::getModel()::pending()->count();
        $flaggedCount = static::getModel()::flagged()->count();
        
        if ($flaggedCount > 0) {
            return 'danger';
        } elseif ($pendingCount > 5) {
            return 'warning';
        }
        
        return 'success';
    }
}
