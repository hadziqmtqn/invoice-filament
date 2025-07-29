<?php

namespace App\Filament\Resources;

use App\Models\Application;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ApplicationResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Application::class;
    protected static ?string $slug = 'application';
    protected static ?string $navigationGroup = 'Pengaturan';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationIcon = 'heroicon-o-cog';

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
                TextInput::make('name')
                    ->required(),

                TextInput::make('email')
                    ->email()
                    ->required(),

                TextInput::make('whatsapp_number')
                    ->required(),

                Grid::make()
                    ->columns()
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('logo')
                            ->label('Logo')
                            ->collection('logo')
                            ->image()
                            ->disk('s3')
                            ->maxSize(200)
                            ->visibility('private')
                            ->dehydrated(fn($state) => filled($state)),
                        SpatieMediaLibraryFileUpload::make('favicon')
                            ->label('Favicon')
                            ->collection('favicon')
                            ->image()
                            ->disk('s3')
                            ->maxSize(50)
                            ->visibility('private')
                            ->dehydrated(fn($state) => filled($state)),
                    ]),

                Grid::make()
                    ->columns()
                    ->schema([
                        Placeholder::make('created_at')
                            ->label('Created At')
                            ->content(fn(Application $record): string => $record->created_at->diffForHumans()),

                        Placeholder::make('updated_at')
                            ->label('Updated At')
                            ->content(fn(Application $record): string => $record->updated_at->diffForHumans()),
                    ]),
            ]);
    }

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

                TextColumn::make('whatsapp_number'),

                SpatieMediaLibraryImageColumn::make('logo')
                    ->collection('logo')
                    ->disk('s3')
                    ->visibility('private')
                    ->label('Logo'),

                SpatieMediaLibraryImageColumn::make('favicon')
                    ->collection('favicon')
                    ->disk('s3')
                    ->visibility('private')
                    ->label('Favicon'),
            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                //
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ApplicationResource\Pages\ListApplications::route('/'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email'];
    }

    public static function canCreate(): bool
    {
        // Only allow create if no data exists
        return Application::count() === 0;
    }
}
