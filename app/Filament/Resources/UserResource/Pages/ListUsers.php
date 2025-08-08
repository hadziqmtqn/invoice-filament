<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Imports\UserImport;
use EightyNine\ExcelImport\ExcelImportAction;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Collection;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExcelImportAction::make()
                ->color("primary")
                ->processCollectionUsing(function (string $modelClass, Collection $collection) {
                    return $collection;
                })
                ->validateUsing([
                    'name' => ['required'],
                    'email' => ['required', 'email', 'unique:users,email'],
                    'password' => ['required', 'min:8'],
                    'phone' => ['required','numeric'],
                ])
                ->beforeUploadField([
                    TextInput::make('name')
                        ->label('Name')
                        ->required(),
                    TextInput::make('email')
                        ->label('Email')
                        ->required()
                        ->email()
                        ->unique('users', 'email'),
                    TextInput::make('password')
                        ->label('Password')
                        ->required()
                        ->password()
                        ->minLength(8),
                    TextInput::make('phone')
                        ->label('Phone')
                        ->required()
                        ->numeric(),
                ])
                ->use(UserImport::class)
                ->sampleFileExcel(asset('assets/new-user.xlsx'), 'Download Sample', fn(Action $action) => $action->color('secondary')
                        ->icon('heroicon-o-clipboard')
                        ->requiresConfirmation(),
                ),
            CreateAction::make(),
        ];
    }
}