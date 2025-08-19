<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LoginAttemptResource\Pages;
use App\Filament\Resources\LoginAttemptResource\RelationManagers;
use App\Models\LoginAttempt;
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

class LoginAttemptResource extends Resource
{
    protected static ?string $model = LoginAttempt::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-exclamation';
    protected static ?string $navigationGroup = 'Security & Monitoring';
    protected static ?string $navigationLabel = 'Login Attempts';
    protected static ?string $modelLabel = 'Login Attempt';
    protected static ?string $pluralModelLabel = 'Login Attempts';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Login Attempt Information')
                    ->description('View login attempt details')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('User')
                            ->options(User::pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->placeholder('Unknown User')
                            ->columnSpanFull(),

                        Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('email')
                                    ->label('Email Address')
                                    ->email()
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('ip_address')
                                    ->label('IP Address')
                                    ->required()
                                    ->maxLength(45),
                            ]),

                        Forms\Components\Textarea::make('user_agent')
                            ->label('User Agent')
                            ->rows(3)
                            ->columnSpanFull(),

                        Grid::make(3)
                            ->schema([
                                Forms\Components\Toggle::make('successful')
                                    ->label('Successful Login')
                                    ->required(),

                                Forms\Components\TextInput::make('token_id')
                                    ->label('Token ID')
                                    ->numeric(),

                                Forms\Components\DateTimePicker::make('attempted_at')
                                    ->label('Attempted At')
                                    ->required()
                                    ->native(false)
                                    ->displayFormat('M j, Y g:i A')
                                    ->default(now()),
                            ]),

                        Forms\Components\Placeholder::make('browser_info')
                            ->label('Browser & Location Information')
                            ->content(function (callable $get) {
                                $userAgent = $get('user_agent');
                                $ipAddress = $get('ip_address');
                                
                                if (!$userAgent && !$ipAddress) {
                                    return 'Enter User Agent and IP Address to see browser and location details';
                                }
                                
                                $browser = 'Unknown';
                                if ($userAgent) {
                                    if (preg_match('/Chrome\/[0-9.]+/', $userAgent)) {
                                        $browser = 'Chrome';
                                    } elseif (preg_match('/Firefox\/[0-9.]+/', $userAgent)) {
                                        $browser = 'Firefox';
                                    } elseif (preg_match('/Safari\/[0-9.]+/', $userAgent)) {
                                        $browser = 'Safari';
                                    } elseif (preg_match('/Edge\/[0-9.]+/', $userAgent)) {
                                        $browser = 'Edge';
                                    }
                                }
                                
                                $location = 'External';
                                if ($ipAddress) {
                                    if (str_starts_with($ipAddress, '127.0.0.1') || str_starts_with($ipAddress, '192.168.')) {
                                        $location = 'Local Network';
                                    }
                                }
                                
                                return new \Illuminate\Support\HtmlString("
                                    <div class='space-y-2 text-sm bg-gray-50 dark:bg-gray-800 p-3 rounded-lg border border-gray-200 dark:border-gray-700'>
                                        <div class='grid grid-cols-2 gap-2'>
                                            <div><strong>Browser:</strong> {$browser}</div>
                                            <div><strong>Location:</strong> {$location}</div>
                                        </div>
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
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->getStateUsing(function ($record) {
                        return $record->successful ? 'Success' : 'Failed';
                    })
                    ->colors([
                        'success' => 'Success',
                        'danger' => 'Failed',
                    ])
                    ->icons([
                        'heroicon-o-check-circle' => 'Success',
                        'heroicon-o-x-circle' => 'Failed',
                    ]),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Unknown User')
                    ->description(fn ($record) => $record->user?->role),

                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->badge()
                    ->color(fn ($record) => str_starts_with($record->ip_address, '127.0.0.1') || str_starts_with($record->ip_address, '192.168.') ? 'warning' : 'info'),

                Tables\Columns\TextColumn::make('browser')
                    ->label('Browser')
                    ->getStateUsing(function ($record) {
                        if (!$record->user_agent) {
                            return 'Unknown';
                        }
                        
                        $userAgent = $record->user_agent;
                        
                        if (preg_match('/Chrome\/[0-9.]+/', $userAgent)) {
                            return 'Chrome';
                        } elseif (preg_match('/Firefox\/[0-9.]+/', $userAgent)) {
                            return 'Firefox';
                        } elseif (preg_match('/Safari\/[0-9.]+/', $userAgent)) {
                            return 'Safari';
                        } elseif (preg_match('/Edge\/[0-9.]+/', $userAgent)) {
                            return 'Edge';
                        } else {
                            return 'Other';
                        }
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Chrome' => 'success',
                        'Firefox' => 'info',
                        'Safari' => 'warning',
                        'Edge' => 'primary',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('location')
                    ->label('Location')
                    ->getStateUsing(function ($record) {
                        if (str_starts_with($record->ip_address, '127.0.0.1') || str_starts_with($record->ip_address, '192.168.')) {
                            return 'Local';
                        }
                        return 'External';
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Local' => 'warning',
                        'External' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('attempted_at')
                    ->label('Attempted At')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->description(fn ($record) => $record->attempted_at->diffForHumans()),

                Tables\Columns\TextColumn::make('token_id')
                    ->label('Token ID')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->copyable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Recorded At')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('successful')
                    ->label('Status')
                    ->options([
                        '1' => 'Successful',
                        '0' => 'Failed',
                    ]),

                Tables\Filters\SelectFilter::make('user_id')
                    ->label('User')
                    ->options(User::pluck('name', 'id'))
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('recent')
                    ->label('Recent Attempts')
                    ->query(fn (Builder $query): Builder => $query->where('attempted_at', '>=', Carbon::now()->subHours(24)))
                    ->toggle(),

                Tables\Filters\Filter::make('failed_attempts')
                    ->label('Failed Attempts Only')
                    ->query(fn (Builder $query): Builder => $query->where('successful', false))
                    ->toggle(),

                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Grid::make(2)
                            ->schema([
                                Forms\Components\DateTimePicker::make('attempted_from')
                                    ->label('Attempted From')
                                    ->native(false),
                                Forms\Components\DateTimePicker::make('attempted_to')
                                    ->label('Attempted To')
                                    ->native(false),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['attempted_from'], fn ($q, $date) => $q->where('attempted_at', '>=', $date))
                            ->when($data['attempted_to'], fn ($q, $date) => $q->where('attempted_at', '<=', $date));
                    }),

                Tables\Filters\Filter::make('ip_address')
                    ->form([
                        Forms\Components\TextInput::make('ip_address')
                            ->label('IP Address')
                            ->placeholder('Enter IP address...'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when($data['ip_address'], fn ($q, $ip) => $q->where('ip_address', 'like', "%{$ip}%"));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                
                Tables\Actions\Action::make('view_user_attempts')
                    ->label('User History')
                    ->icon('heroicon-o-user')
                    ->url(fn (LoginAttempt $record): string => 
                        $record->user 
                            ? route('filament.admin.resources.login-attempts.index', ['tableFilters[user_id][value]' => $record->user_id])
                            : '#'
                    )
                    ->visible(fn (LoginAttempt $record) => $record->user !== null)
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('view_ip_attempts')
                    ->label('IP History')
                    ->icon('heroicon-o-globe-alt')
                    ->url(fn (LoginAttempt $record): string => 
                        route('filament.admin.resources.login-attempts.index', ['tableFilters[ip_address][ip_address]' => $record->ip_address])
                    )
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    Tables\Actions\BulkAction::make('export_attempts')
                        ->label('Export Selected')
                        ->icon('heroicon-o-document-arrow-down')
                        ->action(function ($records) {
                            return redirect()->back()->with('success', 'Export functionality would be implemented here.');
                        })
                        ->requiresConfirmation()
                        ->color('info'),
                ]),
            ])
            ->defaultSort('attempted_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->poll('30s'); 
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
            'index' => Pages\ListLoginAttempts::route('/'),
            'create' => Pages\CreateLoginAttempt::route('/create'),
            'edit' => Pages\EditLoginAttempt::route('/{record}/edit'),
            'view' => Pages\ViewLoginAttempt::route('/{record}'),
        ];
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with(['user']);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['email', 'ip_address', 'user.name', 'user.email'];
    }

    public static function getGlobalSearchResultDetails($record): array
    {
        return [
            'Email' => $record->email,
            'IP Address' => $record->ip_address,
            'User' => $record->user?->name ?? 'Unknown',
            'Status' => $record->successful ? 'Success' : 'Failed',
            'Attempted' => $record->attempted_at?->format('M j, Y g:i A'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::failed()->recent(24)->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $failedCount = static::getModel()::failed()->recent(24)->count();
        
        if ($failedCount > 10) {
            return 'danger';
        } elseif ($failedCount > 5) {
            return 'warning';
        }
        
        return 'success';
    }
}
