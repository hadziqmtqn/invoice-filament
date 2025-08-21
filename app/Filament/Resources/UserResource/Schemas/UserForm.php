<?php

namespace App\Filament\Resources\UserResource\Schemas;

use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;

class UserForm
{
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Tabs')
                    ->columnSpan('full')
                    ->tabs([
                        // TODO Personal data
                        Tabs\Tab::make('Pribadi')
                            ->icon('heroicon-o-user')
                            ->schema([
                                Grid::make()
                                    ->schema([
                                        TextInput::make('name')
                                            ->label('Nama')
                                            ->prefixIcon('heroicon-o-user-circle')
                                            ->minLength(3)
                                            ->required()
                                            ->maxLength(255)
                                            ->placeholder('Masukkan nama lengkap'),

                                        TextInput::make('email')
                                            ->label('Email')
                                            ->prefixIcon('heroicon-o-envelope')
                                            ->email()
                                            ->unique(ignoreRecord: true)
                                            ->required()
                                            ->maxLength(255)
                                            ->placeholder('Masukkan email valid'),

                                        Select::make('roles')
                                            ->label('Peran')
                                            ->prefixIcon('heroicon-o-shield-check')
                                            ->relationship('roles', 'name', fn(Builder $query) => $query->where(['guard_name' => 'web', 'name' => 'user']))
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
                                                    ->label('Tempat Usaha')
                                                    ->prefixIcon('heroicon-o-building-office')
                                                    ->maxLength(50)
                                                    ->dehydrated()
                                                    ->dehydrateStateUsing(fn($state) => $state === '' ? null : $state)
                                                    ->placeholder('Masukkan tempat usaha'),
                                            ]),

                                        Group::make()
                                            ->relationship('userProfile')
                                            ->schema([
                                                TextInput::make('phone')
                                                    ->label('No. Hp')
                                                    ->prefixIcon('heroicon-o-phone')
                                                    ->numeric()
                                                    ->required()
                                                    ->maxLength(15)
                                                    ->unique(ignoreRecord: true)
                                                    ->dehydrated(fn($state) => filled($state))
                                                    ->dehydrateStateUsing(fn($state) => filled($state) ? preg_replace('/[^0-9]/', '', $state) : null)
                                                    ->placeholder('Masukkan No. HP/WHatsapp'),
                                            ])
                                    ]),

                                Grid::make()
                                    ->schema([
                                        TextInput::make('password')
                                            ->label(fn($livewire) => $livewire instanceof EditRecord ? 'Kata Sandi Baru' : 'Kata Sandi')
                                            ->prefixIcon('heroicon-o-lock-closed')
                                            ->password()
                                            ->confirmed()
                                            ->minLength(8)
                                            ->regex('/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/')
                                            ->maxLength(255)
                                            ->autocomplete('new-password')
                                            ->dehydrated(fn (?string $state): bool => filled($state))
                                            ->required(fn (string $operation): bool => $operation === 'create')
                                            ->placeholder(fn($livewire) => $livewire instanceof EditRecord ? 'Biarkan kosong jika Anda tidak ingin mengubah kata sandi' : 'Masukkan Kata Sandi Baru')
                                            ->dehydrateStateUsing(fn($state) => filled($state) ? Hash::make($state) : null)
                                            ->revealable(),

                                        TextInput::make('password_confirmation')
                                            ->label('Konfirmasi Kata Sandi')
                                            ->prefixIcon('heroicon-o-lock-closed')
                                            ->password()
                                            ->minLength(8)
                                            ->maxLength(255)
                                            ->autocomplete('new-password')
                                            ->dehydrated(fn (?string $state): bool => filled($state))
                                            ->required(fn (string $operation): bool => $operation === 'create')
                                            ->placeholder(fn($livewire) => $livewire instanceof EditRecord ? 'Biarkan kosong jika Anda tidak ingin mengubah kata sandi' : 'Konfirmasi Kata Sandi Baru')
                                            ->dehydrateStateUsing(fn($state) => filled($state) ? Hash::make($state) : null)
                                            ->revealable(),
                                    ]),
                            ]),

                        // TODO Address
                        Tabs\Tab::make('Alamat')
                            ->icon('heroicon-o-map-pin')
                            ->schema([
                                Group::make()
                                    ->relationship('userProfile')
                                    ->schema([
                                        Grid::make()
                                            ->columns()
                                            ->schema([
                                                Select::make('province')
                                                    ->label('Provinsi')
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
                                                    ->label('Kota/Kabupaten')
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
                                                    ->label('Kecamatan')
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
                                                    ->label('Desa/Kelurahan')
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
                                                    ->label('Jalan')
                                                    ->maxLength(255)
                                                    ->dehydrated()
                                                    ->placeholder('Nama Jalan')
                                                    ->dehydrateStateUsing(fn($state) => $state === '' ? null : $state),
                                            ]),
                                    ])
                            ]),

                        // TODO Avatar
                        Tabs\Tab::make('Foto Profil')
                            ->icon('heroicon-o-photo')
                            ->schema([
                                SpatieMediaLibraryFileUpload::make('avatar')
                                    ->label('Foto Profil')
                                    ->collection('avatars')
                                    ->image()
                                    ->disk('s3')
                                    ->visibility('private')
                                    ->maxSize(300)
                                    ->dehydrated(fn($state) => filled($state))
                                    ->columnSpanFull(),
                            ])
                    ]),
            ]);
    }
}
