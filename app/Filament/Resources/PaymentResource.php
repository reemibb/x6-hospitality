<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Filament\Resources\PaymentResource\RelationManagers;
use App\Models\Payment;
use App\Models\User;
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
use Illuminate\Support\Str; 
use Carbon\Carbon;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationGroup = 'Financial Management';
    protected static ?string $navigationLabel = 'Payments';
    protected static ?string $modelLabel = 'Payment';
    protected static ?string $pluralModelLabel = 'Payments';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Payment Information')
                    ->description('Create or manage payment records')
                    ->schema([
                        Forms\Components\Select::make('booking_id')
                            ->label('Booking')
                            ->options(function () {
                                return Booking::with(['user', 'room.property'])
                                    ->get()
                                    ->mapWithKeys(function ($booking) {
                                        return [
                                            $booking->booking_id => 
                                            'BK-' . str_pad($booking->booking_id, 6, '0', STR_PAD_LEFT) . 
                                            ' - ' . $booking->user->name . 
                                            ' (' . $booking->room->property->title . ')'
                                        ];
                                    });
                            })
                            ->required()
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(function (callable $set, $state) {
                                if ($state) {
                                    $booking = Booking::with('user')->find($state);
                                    if ($booking) {
                                        $set('user_id', $booking->user_id);
                                        $set('amount', $booking->total_price);
                                    }
                                }
                            })
                            ->columnSpanFull(),

                        Forms\Components\Select::make('user_id')
                            ->label('Customer')
                            ->options(User::pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->disabled()
                            ->dehydrated(),

                        Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('amount')
                                    ->label('Amount')
                                    ->required()
                                    ->numeric()
                                    ->prefix('$')
                                    ->step(0.01)
                                    ->minValue(0.01),

                                Forms\Components\Select::make('currency')
                                    ->label('Currency')
                                    ->options([
                                        'USD' => 'US Dollar (USD)',
                                        'EUR' => 'Euro (EUR)',
                                        'GBP' => 'British Pound (GBP)',
                                        'CAD' => 'Canadian Dollar (CAD)',
                                        'AUD' => 'Australian Dollar (AUD)',
                                        'JPY' => 'Japanese Yen (JPY)',
                                    ])
                                    ->required()
                                    ->default('USD'),

                                Forms\Components\Select::make('payment_method')
                                    ->label('Payment Method')
                                    ->options([
                                        'credit_card' => 'Credit Card',
                                        'debit_card' => 'Debit Card',
                                        'paypal' => 'PayPal',
                                        'bank_transfer' => 'Bank Transfer',
                                        'cash' => 'Cash',
                                        'crypto' => 'Cryptocurrency',
                                        'apple_pay' => 'Apple Pay',
                                        'google_pay' => 'Google Pay',
                                        'stripe' => 'Stripe',
                                        'square' => 'Square',
                                    ])
                                    ->required(),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('payment_status')
                                    ->label('Payment Status')
                                    ->options([
                                        'pending' => 'Pending',
                                        'processing' => 'Processing',
                                        'completed' => 'Completed',
                                        'paid' => 'Paid',
                                        'failed' => 'Failed',
                                        'cancelled' => 'Cancelled',
                                        'refunded' => 'Refunded',
                                        'partially_refunded' => 'Partially Refunded',
                                    ])
                                    ->required()
                                    ->default('pending')
                                    ->reactive(),

                                Forms\Components\TextInput::make('transaction_id')
                                    ->label('Transaction ID')
                                    ->maxLength(255)
                                    ->placeholder('e.g., txn_1234567890')
                                    ->helperText('External payment processor transaction ID'),
                            ]),

                        Forms\Components\DateTimePicker::make('paid_at')
                            ->label('Payment Date')
                            ->native(false)
                            ->displayFormat('M j, Y g:i A')
                            ->visible(fn (callable $get) => in_array($get('payment_status'), ['completed', 'paid', 'partially_refunded', 'refunded']))
                            ->default(now()),

                        // Refund information
                        Section::make('Refund Information')
                            ->schema([
                                Forms\Components\TextInput::make('refunded_amount')
                                    ->label('Refunded Amount')
                                    ->numeric()
                                    ->prefix('$')
                                    ->step(0.01)
                                    ->minValue(0)
                                    ->maxValue(function (callable $get) {
                                        return $get('amount') ?? 0;
                                    })
                                    ->helperText('Amount refunded to customer'),

                                Forms\Components\Textarea::make('refund_reason')
                                    ->label('Refund Reason')
                                    ->rows(2)
                                    ->maxLength(500),

                                Forms\Components\DateTimePicker::make('refunded_at')
                                    ->label('Refunded At')
                                    ->native(false)
                                    ->displayFormat('M j, Y g:i A')
                                    ->default(function (callable $get) {
                                        return $get('refunded_amount') ? now() : null;
                                    }),
                            ])
                            ->columns(3)
                            ->visible(fn (callable $get) => in_array($get('payment_status'), ['partially_refunded', 'refunded'])),

                        Forms\Components\Placeholder::make('booking_details')
                            ->label('Booking Details')
                            ->content(function (callable $get) {
                                $bookingId = $get('booking_id');
                                if ($bookingId) {
                                    $booking = Booking::with(['user', 'room.property'])->find($bookingId);
                                    if ($booking) {
                                        return new \Illuminate\Support\HtmlString("
                                            <div class='space-y-2 text-sm bg-blue-50 dark:bg-blue-900/20 p-3 rounded-lg border border-blue-200 dark:border-blue-800'>
                                                <div class='grid grid-cols-2 gap-2'>
                                                    <div><strong>Guest:</strong> {$booking->user->name}</div>
                                                    <div><strong>Email:</strong> {$booking->user->email}</div>
                                                    <div><strong>Property:</strong> {$booking->room->property->title}</div>
                                                    <div><strong>Room:</strong> {$booking->room->room_type}</div>
                                                    <div><strong>Check-in:</strong> " . $booking->check_in_date->format('M j, Y') . "</div>
                                                    <div><strong>Check-out:</strong> " . $booking->check_out_date->format('M j, Y') . "</div>
                                                    <div><strong>Total Amount:</strong> $" . number_format($booking->total_price, 2) . "</div>
                                                    <div><strong>Booking Status:</strong> <span class='capitalize'>{$booking->status}</span></div>
                                                </div>
                                            </div>
                                        ");
                                    }
                                }
                                return 'Select a booking to see details';
                            })
                            ->columnSpanFull(),

                        Forms\Components\Placeholder::make('payment_summary')
                            ->label('Payment Summary')
                            ->content(function (callable $get) {
                                $amount = $get('amount');
                                $refundedAmount = $get('refunded_amount');
                                $currency = $get('currency') ?? 'USD';
                                $method = $get('payment_method');
                                $status = $get('payment_status');
                                $refundReason = $get('refund_reason');
                                
                                if ($amount && $method && $status) {
                                    $statusColor = match($status) {
                                        'completed', 'paid' => 'green',
                                        'pending' => 'yellow',
                                        'failed', 'cancelled' => 'red',
                                        'refunded', 'partially_refunded' => 'blue',
                                        'processing' => 'purple',
                                        default => 'gray',
                                    };
                                    
                                    $refundInfo = '';
                                    if ($refundedAmount > 0) {
                                        $netAmount = $amount - $refundedAmount;
                                        $refundPercentage = $amount > 0 ? round(($refundedAmount / $amount) * 100, 1) : 0;
                                        
                                        $refundInfo = "
                                            <div class='mt-3 pt-3 border-t border-gray-200 dark:border-gray-700'>
                                                <div><strong>Refunded Amount:</strong> " . number_format($refundedAmount, 2) . " {$currency} ({$refundPercentage}%)</div>
                                                <div><strong>Net Amount:</strong> " . number_format($netAmount, 2) . " {$currency}</div>
                                                " . ($refundReason ? "<div><strong>Reason:</strong> {$refundReason}</div>" : "") . "
                                            </div>
                                        ";
                                    }
                                    
                                    return new \Illuminate\Support\HtmlString("
                                        <div class='space-y-2 text-sm bg-gray-50 dark:bg-gray-800 p-3 rounded-lg border border-gray-200 dark:border-gray-700'>
                                            <div class='grid grid-cols-2 gap-2'>
                                                <div><strong>Amount:</strong> " . number_format($amount, 2) . " {$currency}</div>
                                                <div><strong>Method:</strong> <span class='capitalize'>" . str_replace('_', ' ', $method) . "</span></div>
                                                <div><strong>Status:</strong> <span class='capitalize text-{$statusColor}-600'>{$status}</span></div>
                                                <div><strong>Reference:</strong> PAY-" . str_pad(rand(1, 99999999), 8, '0', STR_PAD_LEFT) . "</div>
                                            </div>
                                            {$refundInfo}
                                        </div>
                                    ");
                                }
                                
                                return 'Complete payment details to see summary';
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
                Tables\Columns\TextColumn::make('payment_reference')
                    ->label('Payment Ref')
                    ->getStateUsing(function ($record) {
                        return 'PAY-' . str_pad($record->payment_id, 8, '0', STR_PAD_LEFT);
                    })
                    ->badge()
                    ->color('primary')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('payment_id', 'like', "%{$search}%");
                    })
                    ->copyable(),

                Tables\Columns\BadgeColumn::make('payment_status')
                    ->label('Status')
                    ->colors([
                        'success' => fn ($state) => in_array($state, ['completed', 'paid']),
                        'warning' => 'pending',
                        'danger' => fn ($state) => in_array($state, ['failed', 'cancelled']),
                        'info' => 'refunded',
                        'primary' => fn ($state) => in_array($state, ['processing', 'partially_refunded']),
                    ])
                    ->icons([
                        'heroicon-o-check-circle' => fn ($state) => in_array($state, ['completed', 'paid']),
                        'heroicon-o-clock' => 'pending',
                        'heroicon-o-x-circle' => fn ($state) => in_array($state, ['failed', 'cancelled']),
                        'heroicon-o-arrow-path' => fn ($state) => in_array($state, ['refunded', 'partially_refunded']),
                        'heroicon-o-cog-6-tooth' => 'processing',
                    ]),

                Tables\Columns\TextColumn::make('booking.booking_reference')
                    ->label('Booking')
                    ->getStateUsing(function ($record) {
                        return $record->booking ? 'BK-' . str_pad($record->booking->booking_id, 6, '0', STR_PAD_LEFT) : 'N/A';
                    })
                    ->searchable()
                    ->sortable()
                    ->url(fn ($record) => $record->booking ? route('filament.admin.resources.bookings.edit', $record->booking) : null)
                    ->openUrlInNewTab(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->user->email),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->money(fn ($record) => $record->currency ?? 'USD')
                    ->sortable()
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('refunded_amount')
                    ->label('Refunded')
                    ->money(fn ($record) => $record->currency ?? 'USD')
                    ->sortable()
                    ->alignEnd()
                    ->default(0)
                    ->visible(fn ($livewire) => $livewire instanceof Pages\ListPayments),

                Tables\Columns\TextColumn::make('net_amount')
                    ->label('Net Amount')
                    ->getStateUsing(fn ($record) => $record->amount - ($record->refunded_amount ?? 0))
                    ->money(fn ($record) => $record->currency ?? 'USD')
                    ->sortable()
                    ->alignEnd()
                    ->color(fn ($record) => $record->refunded_amount > 0 ? 'warning' : 'success')
                    ->visible(fn ($livewire) => $livewire instanceof Pages\ListPayments),

                Tables\Columns\BadgeColumn::make('payment_method')
                    ->label('Method')
                    ->formatStateUsing(fn (string $state): string => str_replace('_', ' ', Str::title($state)))
                    ->colors([
                        'success' => fn ($state) => in_array($state, ['credit_card', 'debit_card']),
                        'info' => fn ($state) => in_array($state, ['paypal', 'apple_pay', 'google_pay']),
                        'warning' => 'bank_transfer',
                        'primary' => 'cash',
                        'purple' => 'crypto',
                        'gray' => fn ($state) => in_array($state, ['stripe', 'square']),
                    ]),

                Tables\Columns\TextColumn::make('transaction_id')
                    ->label('Transaction ID')
                    ->limit(15)
                    ->copyable()
                    ->placeholder('N/A')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('paid_at')
                    ->label('Paid At')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->placeholder('Not paid')
                    ->description(fn ($record) => $record->paid_at?->diffForHumans()),

                Tables\Columns\TextColumn::make('refunded_at')
                    ->label('Refunded At')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->placeholder('Not refunded')
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\IconColumn::make('is_overdue')
                    ->label('Overdue')
                    ->getStateUsing(function ($record) {
                        return $record->payment_status === 'pending' && 
                               $record->created_at->diffInDays(now()) > 7;
                    })
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('danger')
                    ->falseColor('success')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('payment_status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'processing' => 'Processing',
                        'completed' => 'Completed',
                        'paid' => 'Paid',
                        'failed' => 'Failed',
                        'cancelled' => 'Cancelled',
                        'refunded' => 'Refunded',
                        'partially_refunded' => 'Partially Refunded',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('payment_method')
                    ->label('Payment Method')
                    ->options([
                        'credit_card' => 'Credit Card',
                        'debit_card' => 'Debit Card',
                        'paypal' => 'PayPal',
                        'bank_transfer' => 'Bank Transfer',
                        'cash' => 'Cash',
                        'crypto' => 'Cryptocurrency',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('currency')
                    ->options([
                        'USD' => 'USD',
                        'EUR' => 'EUR',
                        'GBP' => 'GBP',
                        'CAD' => 'CAD',
                    ]),

                Tables\Filters\Filter::make('has_refund')
                    ->label('Has Refund')
                    ->query(fn (Builder $query): Builder => 
                        $query->whereNotNull('refunded_amount')->where('refunded_amount', '>', 0)
                    )
                    ->toggle(),

                Tables\Filters\Filter::make('amount_range')
                    ->form([
                        Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('amount_from')
                                    ->label('Amount From')
                                    ->numeric()
                                    ->prefix('$'),
                                Forms\Components\TextInput::make('amount_to')
                                    ->label('Amount To')
                                    ->numeric()
                                    ->prefix('$'),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['amount_from'], fn ($q, $amount) => $q->where('amount', '>=', $amount))
                            ->when($data['amount_to'], fn ($q, $amount) => $q->where('amount', '<=', $amount));
                    }),

                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('paid_from')
                                    ->label('Paid From')
                                    ->native(false),
                                Forms\Components\DatePicker::make('paid_to')
                                    ->label('Paid To')
                                    ->native(false),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['paid_from'], fn ($q, $date) => $q->where('paid_at', '>=', $date))
                            ->when($data['paid_to'], fn ($q, $date) => $q->where('paid_at', '<=', $date));
                    }),

                Tables\Filters\Filter::make('overdue')
                    ->label('Overdue Payments')
                    ->query(fn (Builder $query): Builder => 
                        $query->where('payment_status', 'pending')
                              ->where('created_at', '<', Carbon::now()->subDays(7))
                    )
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                
                Tables\Actions\Action::make('mark_paid')
                    ->label('Mark as Paid')
                    ->icon('heroicon-o-check-circle')
                    ->action(function (Payment $record) {
                        $record->update([
                            'payment_status' => 'paid',
                            'paid_at' => now(),
                        ]);
                    })
                    ->visible(fn (Payment $record) => $record->payment_status === 'pending')
                    ->color('success')
                    ->requiresConfirmation(),

                Tables\Actions\Action::make('mark_failed')
                    ->label('Mark as Failed')
                    ->icon('heroicon-o-x-circle')
                    ->action(function (Payment $record) {
                        $record->update(['payment_status' => 'failed']);
                    })
                    ->visible(fn (Payment $record) => in_array($record->payment_status, ['pending', 'processing']))
                    ->color('danger')
                    ->requiresConfirmation(),

                Tables\Actions\Action::make('refund')
                    ->label('Process Refund')
                    ->icon('heroicon-o-arrow-path')
                    ->form([
                        Forms\Components\TextInput::make('refund_amount')
                            ->label('Refund Amount')
                            ->numeric()
                            ->prefix('$')
                            ->step(0.01)
                            ->minValue(0.01)
                            ->maxValue(function ($record) {
                                return $record->amount - ($record->refunded_amount ?? 0);
                            })
                            ->required()
                            ->default(function ($record) {
                                return $record->amount - ($record->refunded_amount ?? 0);
                            }),
                        Forms\Components\Textarea::make('refund_reason')
                            ->label('Refund Reason')
                            ->required(),
                    ])
                    ->action(function (Payment $record, array $data) {
                        $totalRefundedAmount = ($record->refunded_amount ?? 0) + $data['refund_amount'];
                        $fullRefund = $totalRefundedAmount >= $record->amount;
                        
                        $record->update([
                            'payment_status' => $fullRefund ? 'refunded' : 'partially_refunded',
                            'refunded_amount' => $totalRefundedAmount,
                            'refund_reason' => $data['refund_reason'],
                            'refunded_at' => now(),
                        ]);
                    })
                    ->visible(fn (Payment $record) => $record->is_refundable)
                    ->color('info')
                    ->requiresConfirmation(),

                Tables\Actions\Action::make('view_booking')
                    ->label('View Booking')
                    ->icon('heroicon-o-calendar')
                    ->url(fn (Payment $record) => $record->booking ? route('filament.admin.resources.bookings.edit', $record->booking) : null)
                    ->visible(fn (Payment $record) => $record->booking !== null)
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    Tables\Actions\BulkAction::make('mark_paid_bulk')
                        ->label('Mark as Paid')
                        ->icon('heroicon-o-check-circle')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                if ($record->payment_status === 'pending') {
                                    $record->update([
                                        'payment_status' => 'paid',
                                        'paid_at' => now(),
                                    ]);
                                }
                            });
                        })
                        ->requiresConfirmation()
                        ->color('success'),

                    Tables\Actions\BulkAction::make('export_payments')
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
            'index' => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayment::route('/create'),
            'edit' => Pages\EditPayment::route('/{record}/edit'),
        ];
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with(['user', 'booking']);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['transaction_id', 'user.name', 'user.email', 'amount'];
    }

    public static function getGlobalSearchResultDetails($record): array
    {
        return [
            'Customer' => $record->user?->name,
            'Amount' => '$' . number_format($record->amount, 2),
            'Status' => $record->payment_status,
            'Method' => str_replace('_', ' ', Str::title($record->payment_method)),
            'Date' => $record->paid_at?->format('M j, Y') ?? 'Not paid',
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::pending()->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $pendingCount = static::getModel()::pending()->count();
        $overdueCount = static::getModel()::where('payment_status', 'pending')
                                        ->where('created_at', '<', Carbon::now()->subDays(7))
                                        ->count();
        
        if ($overdueCount > 0) {
            return 'danger';
        } elseif ($pendingCount > 10) {
            return 'warning';
        }
        
        return 'success';
    }
}
