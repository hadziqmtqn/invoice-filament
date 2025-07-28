<?php

namespace App\Filament\Resources\Main;

use App\Filament\Resources\Main\UserResource\Pages;
use App\Models\User;
use Coolsam\Flatpickr\Forms\Components\Flatpickr;
use Exception;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $slug = 'main/users';
    protected static ?string $navigationGroup = 'Main';

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Fieldset::make('user')
                    ->label('User Information')
                    ->schema([
                        TextInput::make('name')
                            ->label('Name')
                            ->minLength(3)
                            ->required()
                            ->maxLength(255),

                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->unique(ignoreRecord: true)
                            ->required()
                            ->maxLength(255),

                        Select::make('roles')
                            ->label('Role')
                            ->relationship('roles', 'name')
                            ->preload()
                            ->required()
                            ->rules([
                                Rule::exists('roles', 'id')
                                    ->where('guard_name', 'web'),
                            ])
                            ->searchable()
                            ->columnSpanFull(),
                    ]),

                Fieldset::make('address')
                    ->label('Address')
                    ->relationship('userProfile')
                    ->schema([
                        TextInput::make('phone')
                            ->label('Phone')
                            ->numeric()
                            ->required()
                            ->maxLength(15)
                            ->rules([
                                function (Get $get, $livewire) {
                                    $userProfile = $livewire->record?->userProfile;
                                    $currentPhone = $userProfile?->phone;
                                    $inputPhone = preg_replace('/[^0-9]/', '', $get('phone'));

                                    // Only apply unique rule if phone is changed or new
                                    if ($inputPhone !== $currentPhone) {
                                        $rule = Rule::unique('user_profiles', 'phone');
                                        if ($userProfile?->id) {
                                            $rule->ignore($userProfile->id, 'id');
                                        }
                                        return $rule;
                                    }
                                    return null;
                                },
                            ])
                            ->dehydrated(fn($state) => filled($state))
                            ->dehydrateStateUsing(fn($state) => filled($state) ? preg_replace('/[^0-9]/', '', $state) : null),
                        TextInput::make('province'),
                        TextInput::make('city'),
                        TextInput::make('district'),
                        TextInput::make('village'),
                    ]),

                Flatpickr::make('email_verified_at')
                    ->label('Email Verified Date')
                    ->placeholder('Select date')
                    ->maxDate(fn() => now())
                    ->default(fn() => now()),

                TextInput::make('password')
                    ->label('Password')
                    ->password()
                    ->minLength(8)
                    // use number, uppercase, lowercase, and special characters
                    ->regex('/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/')
                    ->hint('Password must contain at least 8 characters, including uppercase, lowercase, number, and special character.')
                    ->maxLength(255)
                    ->autocomplete('new-password')
                    ->columnSpanFull()
                    ->label(fn($livewire) => $livewire instanceof EditRecord ? 'New Password' : 'Password')
                    ->dehydrated(fn($state) => filled($state))
                    ->required(fn($livewire) => $livewire instanceof CreateRecord)
                    ->placeholder(fn($livewire) => $livewire instanceof EditRecord ? 'Biarkan kosong jika tidak ingin mengubah password' : null)
                    ->dehydrated(fn($state) => filled($state))
                    ->dehydrateStateUsing(fn($state) => filled($state) ? Hash::make($state) : null),

                Placeholder::make('created_at')
                    ->label('Created Date')
                    ->content(fn(?User $record): string => $record?->created_at?->diffForHumans() ?? '-'),

                Placeholder::make('updated_at')
                    ->label('Last Modified Date')
                    ->content(fn(?User $record): string => $record?->updated_at?->diffForHumans() ?? '-'),
            ]);
    }

    /**
     * @throws Exception
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('roles.name')
                    ->label('Role')
                    ->badge()
                    ->color(fn(string $state) => match ($state) {
                        'super_admin' => 'primary',
                        'user' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state) => ucfirst(str_replace('_', ' ', $state)))
                    ->sortable(),

                TextColumn::make('userProfile.phone')
                    ->label('Phone')
                    ->searchable(),
            ])
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('role')
                    ->label('Role')
                    ->options(fn () => Role::all()->pluck('name', 'id'))
                    ->query(function (Builder $query, array $data) {
                        if ($data['value']) {
                            $query->whereHas('roles', function ($q) use ($data) {
                                $q->where('id', $data['value']);
                            });
                        }
                    }),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make()
                    ->disabled(fn(User $record): bool => $record->hasRole('super_admin')),
                RestoreAction::make(),
                ForceDeleteAction::make()
                    ->disabled(fn(User $record): bool => $record->hasRole('super_admin')),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('userProfile', 'roles')
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email', 'userProfile.phone'];
    }
}
