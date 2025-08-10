<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\Pages\ManageInvoices;
use App\Filament\Resources\UserResource\Pages\PaymentHistory;
use App\Jobs\ChangeAuthenticationMessageJob;
use App\Models\User;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Exception;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use Spatie\Permission\Models\Role;

class UserResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = User::class;

    protected static ?string $slug = 'users';

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function getPermissionPrefixes(): array
    {
        // TODO: Implement getPermissionPrefixes() method.
        return [
            'view_any',
            'view',
            'create',
            'update',
            'delete',
            'restore',
            'restore_any',
            'force_delete',
            'force_delete_any',
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Tabs')
                    ->columnSpan('full')
                    ->tabs([
                        // TODO Personal data
                        Tabs\Tab::make('Personal')
                            ->icon('heroicon-o-user')
                            ->schema([
                                Grid::make()
                                    ->schema([
                                        TextInput::make('name')
                                            ->label('Name')
                                            ->prefixIcon('heroicon-o-user-circle')
                                            ->minLength(3)
                                            ->required()
                                            ->maxLength(255)
                                            ->placeholder('Enter your name'),

                                        TextInput::make('email')
                                            ->label('Email')
                                            ->prefixIcon('heroicon-o-envelope')
                                            ->email()
                                            ->unique(ignoreRecord: true)
                                            ->required()
                                            ->maxLength(255)
                                            ->placeholder('Enter your email'),

                                        Select::make('roles')
                                            ->label('Role')
                                            ->prefixIcon('heroicon-o-shield-check')
                                            ->relationship('roles', 'name')
                                            ->preload()
                                            ->required()
                                            ->rules([
                                                Rule::exists('roles', 'id')->where('guard_name', 'web'),
                                            ])
                                            ->searchable(),

                                        Group::make()
                                            ->relationship('userProfile')
                                            ->schema([
                                                TextInput::make('company_name')
                                                    ->label('Company Name')
                                                    ->prefixIcon('heroicon-o-building-office')
                                                    ->maxLength(50)
                                                    ->dehydrated()
                                                    ->dehydrateStateUsing(fn($state) => $state === '' ? null : $state)
                                                    ->placeholder('Enter your company name'),
                                            ]),

                                        Group::make()
                                            ->relationship('userProfile')
                                            ->schema([
                                                TextInput::make('phone')
                                                    ->label('Phone')
                                                    ->prefixIcon('heroicon-o-phone')
                                                    ->numeric()
                                                    ->required()
                                                    ->maxLength(15)
                                                    ->unique(ignoreRecord: true)
                                                    ->dehydrated(fn($state) => filled($state))
                                                    ->dehydrateStateUsing(fn($state) => filled($state) ? preg_replace('/[^0-9]/', '', $state) : null)
                                                    ->placeholder('Enter your phone number'),
                                            ])
                                    ]),

                                Grid::make()
                                    ->schema([
                                        TextInput::make('password')
                                            ->label(fn($livewire) => $livewire instanceof EditRecord ? 'New Password' : 'Password')
                                            ->prefixIcon('heroicon-o-lock-closed')
                                            ->password()
                                            ->confirmed()
                                            ->minLength(8)
                                            ->regex('/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/')
                                            ->maxLength(255)
                                            ->autocomplete('new-password')
                                            ->dehydrated(fn (?string $state): bool => filled($state))
                                            ->required(fn (string $operation): bool => $operation === 'create')
                                            ->placeholder(fn($livewire) => $livewire instanceof EditRecord ? 'Leave it blank if you don\'t want to change the password' : 'Enter new password')
                                            ->dehydrateStateUsing(fn($state) => filled($state) ? Hash::make($state) : null)
                                            ->revealable(),

                                        TextInput::make('password_confirmation')
                                            ->label('Confirm Password')
                                            ->prefixIcon('heroicon-o-lock-closed')
                                            ->password()
                                            ->minLength(8)
                                            ->maxLength(255)
                                            ->autocomplete('new-password')
                                            ->dehydrated(fn (?string $state): bool => filled($state))
                                            ->required(fn (string $operation): bool => $operation === 'create')
                                            ->placeholder(fn($livewire) => $livewire instanceof EditRecord ? 'Leave it blank if you don\'t want to change the password' : 'Confirm new password')
                                            ->dehydrateStateUsing(fn($state) => filled($state) ? Hash::make($state) : null)
                                            ->revealable(),
                                    ]),
                            ]),

                        // TODO Address
                        Tabs\Tab::make('Address')
                            ->icon('heroicon-o-map-pin')
                            ->schema([
                                Group::make()
                                    ->relationship('userProfile')
                                    ->schema([
                                        Grid::make()
                                            ->columns()
                                            ->schema([
                                                Select::make('province')
                                                    ->label('Province')
                                                    ->searchable()
                                                    ->getSearchResultsUsing(function (string $search) {
                                                        $response = Http::get('https://idn-location.bkn.my.id/api/v1/provinces', [
                                                            'q' => $search,
                                                        ]);
                                                        return collect($response->json())->pluck('name', 'name')->toArray();
                                                    })
                                                    ->getOptionLabelUsing(fn ($value) => $value)
                                                    ->dehydrated()
                                                    ->dehydrateStateUsing(fn($state) => $state === '' ? null : $state)
                                                    ->reactive()
                                                    ->afterStateUpdated(function ($state, callable $set) {
                                                        $set('city', null);
                                                        $set('district', null);
                                                        $set('village', null);
                                                    }),

                                                Select::make('city')
                                                    ->label('City')
                                                    ->searchable()
                                                    ->getSearchResultsUsing(function (string $search, $get) {
                                                        $province = $get('province');
                                                        if (!$province) return [];
                                                        $response = Http::get('https://idn-location.bkn.my.id/api/v1/cities', [
                                                            'province' => $province,
                                                            'q' => $search,
                                                        ]);
                                                        return collect($response->json())->pluck('name', 'name')->toArray();
                                                    })
                                                    ->getOptionLabelUsing(fn ($value) => $value)
                                                    ->dehydrated()
                                                    ->dehydrateStateUsing(fn($state) => $state === '' ? null : $state)
                                                    ->reactive()
                                                    ->afterStateUpdated(function ($state, callable $set) {
                                                        $set('district', null);
                                                        $set('village', null);
                                                    }),

                                                Select::make('district')
                                                    ->label('District')
                                                    ->searchable()
                                                    ->getSearchResultsUsing(function (string $search, $get) {
                                                        $city = $get('city');
                                                        if (!$city) return [];
                                                        $response = Http::get('https://idn-location.bkn.my.id/api/v1/districts', [
                                                            'city' => $city,
                                                            'q' => $search,
                                                        ]);
                                                        return collect($response->json())->pluck('name', 'name')->toArray();
                                                    })
                                                    ->getOptionLabelUsing(fn ($value) => $value)
                                                    ->dehydrated()
                                                    ->dehydrateStateUsing(fn($state) => $state === '' ? null : $state)
                                                    ->reactive()
                                                    ->afterStateUpdated(function ($state, callable $set) {
                                                        $set('village', null);
                                                    }),

                                                Select::make('village')
                                                    ->label('Village')
                                                    ->searchable()
                                                    ->getSearchResultsUsing(function (string $search, $get) {
                                                        $district = $get('district');
                                                        if (!$district) return [];
                                                        $response = Http::get('https://idn-location.bkn.my.id/api/v1/villages', [
                                                            'district' => $district,
                                                            'q' => $search,
                                                        ]);
                                                        return collect($response->json())->pluck('name', 'name')->toArray();
                                                    })
                                                    ->getOptionLabelUsing(fn ($value) => $value)
                                                    ->dehydrated()
                                                    ->dehydrateStateUsing(fn($state) => $state === '' ? null : $state)
                                                    ->reactive(),

                                                TextInput::make('street')
                                                    ->label('Street')
                                                    ->maxLength(255)
                                                    ->dehydrated()
                                                    ->dehydrateStateUsing(fn($state) => $state === '' ? null : $state),
                                            ]),
                                    ])
                            ]),

                        // TODO Avatar
                        Tabs\Tab::make('Avatar')
                            ->icon('heroicon-o-photo')
                            ->schema([
                                SpatieMediaLibraryFileUpload::make('avatar')
                                    ->label('Avatar')
                                    ->collection('avatars')
                                    ->image()
                                    ->disk('s3')
                                    ->visibility('private')
                                    ->maxSize(300)
                                    ->dehydrated(fn($state) => filled($state))
                                    ->columnSpanFull(),
                            ])
                    ]),

                Placeholder::make('created_at')
                    ->label('Created Date')
                    ->content(fn(?User $record): string => $record?->created_at?->diffForHumans() ?? '-')
                    ->visible(fn(?User $record): bool => $record?->created_at !== null),

                Placeholder::make('updated_at')
                    ->label('Last Modified Date')
                    ->content(fn(?User $record): string => $record?->updated_at?->diffForHumans() ?? '-')
                    ->visible(fn(?User $record): bool => $record?->updated_at !== null),
            ]);
    }

    /**
     * @throws Exception
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('avatar')
                    ->collection('avatars')
                    ->disk('s3')
                    ->visibility('private')
                    ->defaultImageUrl(fn ($record) => $record->default_avatar)
                    ->circular()
                    ->size(40),

                TextColumn::make('name')
                    ->description(fn(User $record): ?string => $record->userProfile?->company_name)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('receivables')
                    ->label('Receivables')
                    ->money('idr'),

                TextColumn::make('roles.name')
                    ->label('Role')
                    ->badge()
                    ->color(fn(string $state) => match ($state) {
                        'super_admin' => 'primary',
                        'user' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state) => ucfirst(str_replace('_', ' ', $state)))
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),

                TextColumn::make('userProfile.phone')
                    ->label('Phone')
                    ->searchable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('role')
                    ->label('Role')
                    ->options(fn () => Role::all()->pluck('name', 'id'))
                    ->query(function (Builder $query, array $data) {
                        if ($data['value']) {
                            $query->whereHas('roles', function ($q) use ($data) {
                                $q->where('id', $data['value']);
                            });
                        }
                    })
                    ->native(false),
                SelectFilter::make('receivables')
                    ->options([
                        'YES' => 'Have a debt',
                        'NO' => 'No debt',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if ($data['value'] === 'YES') {
                            $query->whereHas('invoices', function (Builder $q) {
                                $q->where('status', '!=', 'paid');
                            });
                        } elseif ($data['value'] === 'NO') {
                            $query->whereDoesntHave('invoices', function (Builder $q) {
                                $q->where('status', '!=', 'paid');
                            });
                        }
                    })
                    ->native(false),
                TrashedFilter::make()
                    ->native(false),
            ])
            ->headerActions([
                ExportAction::make()->exports([
                    ExcelExport::make()
                        ->modifyQueryUsing(fn(Builder $query) => $query->whereHas('roles', function (Builder $query) {
                            $query->where('name', 'user');
                        }))
                        ->withColumns([
                            Column::make('name')->heading('Name'),
                            Column::make('email')->heading('Email'),
                            Column::make('userProfile.company_name')->heading('Company Name'),
                            Column::make('userProfile.phone')->heading('Phone'),
                            Column::make('receivables')->heading('Receivables')
                                ->formatStateUsing(fn($state) => number_format($state, 0, ',', '.')),
                        ])
                        ->queue()
                        ->withChunkSize(100)
                        ->askForFilename()
                        ->withFilename(fn ($filename) => time() . '-' . $filename)
                        ->askForWriterType()
                ])
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make()
                        ->before(function ($record, $data) {
                            $emailChanged = $data['email'] !== $record->email;
                            $passwordChanged = !empty($data['password'] ?? '');

                            if ($emailChanged || $passwordChanged) {
                                ChangeAuthenticationMessageJob::dispatch([
                                    'user_name' => $record->name,
                                    'email' => $data['email'],
                                    'password_changed' => $passwordChanged,
                                ], $record->userProfile?->phone);
                            }
                        }),
                    DeleteAction::make()
                        ->disabled(fn(User $record): bool => $record->hasRole('super_admin') || $record->invoices()->exists() || $record->payments()->exists()),
                    RestoreAction::make(),
                    ForceDeleteAction::make()
                        ->disabled(fn(User $record): bool => $record->hasRole('super_admin')),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    //
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => UserResource\Pages\ListUsers::route('/'),
            'edit' => UserResource\Pages\EditUser::route('/{record}/edit'),
            'manage-invoices' => UserResource\Pages\ManageInvoices::route('/{record}/invoices'),
            'payment-history' => UserResource\Pages\PaymentHistory::route('/{record}/payment-history'),
        ];
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            EditUser::class,
            ManageInvoices::class,
            PaymentHistory::class
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('userProfile', 'roles')
            ->whereHas('roles', function (Builder $query) {
                $query->where('name', 'user');
            })
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email', 'userProfile.phone'];
    }
}
