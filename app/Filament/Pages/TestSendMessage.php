<?php

namespace App\Filament\Pages;

use App\Jobs\SendWhatsappMessageJob;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * @property mixed $form
 */
class TestSendMessage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Configuration';
    protected static ?int $navigationSort = 5;
    protected static string $view = 'filament.pages.test-send-whatsapp-message-page';

    public mixed $data = [];

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                 TextInput::make('whatsappNumber')
                     ->required(),

                Textarea::make('message')
                    ->required()
                    ->maxLength(1000)
                    ->placeholder('Type your message here...'),

                Actions::make([
                    Action::make('send')
                        ->label('Send Message')
                        ->icon('heroicon-o-paper-airplane')
                        ->action('send')
                ])
            ])
            ->statePath('data');
    }

    public function send(): void
    {
        // Validasi data
        $validate = $this->form->validate();

        // Dispatch Job
        SendWhatsappMessageJob::dispatch(
            $validate['data']['whatsappNumber'],
            $validate['data']['message']
        );

        // Optional: Tampilkan notifikasi sukses
        Notification::make()
            ->title('Pesan dikirim ke WhatsApp!')
            ->success()
            ->send();
    }
}
