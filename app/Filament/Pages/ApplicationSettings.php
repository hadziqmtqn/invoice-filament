<?php

namespace App\Filament\Pages;

use App\Models\Application;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification; // <--- Tambahkan ini

class ApplicationSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $title = 'Application Settings';
    protected static ?string $navigationGroup = 'Settings';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'Application Settings';
    protected static ?string $slug = 'application-settings';
    protected static ?string $navigationIcon = 'heroicon-o-cog';
    protected static string $view = 'filament.pages.application-settings';

    public array $data = [];

    public function mount(): void
    {
        $application = Application::first();
        if ($application) {
            $this->data = $application->only(['name', 'email', 'whatsapp_number']);
        }
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')->required(),
            TextInput::make('email')->email()->required(),
            TextInput::make('whatsapp_number')->required(),
        ])->statePath('data');
    }

    public function submit(): void
    {
        $application = Application::first();
        $application?->update($this->data);

        Notification::make()
            ->title('Application updated!')
            ->success()
            ->send();
    }
}