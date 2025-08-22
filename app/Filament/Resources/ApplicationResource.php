<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ApplicationResource\Pages;
use App\Models\Application;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;

class ApplicationResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Application::class;
    protected static ?string $slug = 'application';
    protected static ?string $navigationLabel = 'Aplikasi';

    public static function getNavigationUrl(): string
    {
        return static::getUrl('edit', ['record' => Application::first()?->getRouteKey()]);
    }

    public static function getPermissionPrefixes(): array
    {
        // TODO: Implement getPermissionPrefixes() method.
        return [
            'view',
            'edit',
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Base Data')
                    ->description('Base data about this application')
                    ->aside()
                    ->schema([
                        TextInput::make('name')
                            ->required(),

                        TextInput::make('email')
                            ->email()
                            ->required(),

                        TextInput::make('whatsapp_number')
                            ->required(),
                    ]),

                    Section::make('Assets')
                        ->aside()
                        ->schema([
                            FileUpload::make('invoice_logo')
                                ->disk('public')
                                ->directory('invoices')
                                ->image()
                                ->maxSize(200),

                            SpatieMediaLibraryFileUpload::make('logo')
                                ->label('Logo')
                                ->collection('logo')
                                ->image()
                                ->disk('s3_public')
                                ->maxSize(200)
                                ->openable()
                                ->dehydrated(fn($state) => filled($state)),

                            SpatieMediaLibraryFileUpload::make('favicon')
                                ->label('Favicon')
                                ->collection('favicon')
                                ->image()
                                ->disk('s3_public')
                                ->maxSize(50)
                                ->openable()
                                ->dehydrated(fn($state) => filled($state)),
                        ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListApplications::route('/'),
            'edit' => Pages\EditApplication::route('/{record}'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return [];
    }

    public static function canCreate(): bool
    {
        // Only allow create if no data exists
        return Application::count() === 0;
    }
}
