<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class AccountPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';
    protected static ?string $navigationGroup = 'Settings';
    protected static ?string $navigationLabel = 'Account';
    protected static ?int $navigationSort = 1;
    protected static string $view = 'filament.pages.account-page';

    public string $name;
    public string $email;

    public function mount(): void
    {
        $user = Auth::user();
        $this->name = $user->name;
        $this->email = $user->email;
    }

    public function form(Form $form): Form
    {
        return $form
            ->model($this->getFormModel())
            ->schema([
                Fieldset::make('Informasi Akun')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Lengkap')
                            ->required()
                            ->maxLength(255)
                            ->default($this->name),
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->default($this->email),
                    ]),
            ]);
    }

    public function submit(): void
    {
        $user = Auth::user();
        $user->name = $this->name;
        $user->email = $this->email;
        $user->save();

        Notification::make()
            ->title('Akun berhasil diperbarui!')
            ->success()
            ->send();
    }

    protected function getFormModel(): string
    {
        return Auth::user()::class;
    }
}
