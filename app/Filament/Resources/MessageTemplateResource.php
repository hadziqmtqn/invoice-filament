<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MessageTemplateResource\Pages;
use App\Models\MessageTemplate;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class MessageTemplateResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = MessageTemplate::class;
    protected static ?string $slug = 'message-templates';
    protected static ?string $navigationGroup = 'Configuration';
    protected static ?int $navigationSort = 5;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    public static function getPermissionPrefixes(): array
    {
        // TODO: Implement getPermissionPrefixes() method.
        return [
            'view_any',
            'view',
            'create',
            'update',
            'delete',
            'delete_any',
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('category')
                    ->required()
                    ->options([
                        'change-authentication' => 'Change Authentication',
                        'unpaid-bill' => 'Unpaid Bill',
                    ])
                    ->searchable(),

                TextInput::make('title')
                    ->required()
                    ->afterStateUpdated(fn($state, callable $set) => $set('slug', Str::slug($state))),

                Textarea::make('message')
                    ->required()
                    ->autosize()
                    ->columnSpanFull(),

                Checkbox::make('is_active')
                    ->visible(fn(?MessageTemplate $record): bool => $record?->exists ?? false),

                Grid::make()
                    ->columns()
                    ->schema([
                        Placeholder::make('created_at')
                            ->label('Created Date')
                            ->visible(fn(?MessageTemplate $record): bool => $record?->exists ?? false)
                            ->content(fn(?MessageTemplate $record): string => $record?->created_at?->diffForHumans() ?? '-'),

                        Placeholder::make('updated_at')
                            ->label('Last Modified Date')
                            ->visible(fn(?MessageTemplate $record): bool => $record?->exists ?? false)
                            ->content(fn(?MessageTemplate $record): string => $record?->updated_at?->diffForHumans() ?? '-'),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('category')
                    ->formatStateUsing(fn($state) => Str::of($state)->replace('-', ' ')->title())
                    ->searchable()
                    ->sortable(),

                TextColumn::make('title')
                    ->wrap()
                    ->searchable()
                    ->sortable(),

                ToggleColumn::make('is_active')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make()
                        ->modalHeading('Detail Message Template')
                        ->modalContent(),
                    EditAction::make(),
                    DeleteAction::make(),
                ])
                    ->link()
                    ->label('Actions'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMessageTemplates::route('/'),
            /*'create' => Pages\CreateMessageTemplate::route('/create'),
            'edit' => Pages\EditMessageTemplate::route('/{record}/edit'),*/
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['slug', 'title'];
    }
}
