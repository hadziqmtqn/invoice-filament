<?php

namespace App\Filament\Resources\PaymentResource\Actions;

use App\Enums\DataStatus;
use App\Enums\PaymentSource;
use App\Models\Payment;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class HeaderAction
{
    public static function headerAction(): array
    {
        return [
            Action::make('pay')
                ->label('Bayar Sekarang')
                ->icon('heroicon-o-currency-dollar')
                ->requiresConfirmation()
                ->modalDescription('Apakah yakin akan bayar sekarang?')
                ->modalIconColor('danger')
                ->modalWidth('sm')
                ->action(function (Payment $record, array $data, $livewire) {
                    $snapToken = $record->midtrans_snap_token;

                    if ($snapToken) {
                        $livewire->dispatch('midtrans-pay', $snapToken);
                    } else {
                        Notification::make()
                            ->title('Gagal memproses pembayaran')
                            ->body('Terjadi kesalahan saat membuat pembayaran. Silakan coba lagi.')
                            ->danger()
                            ->send();
                    }
                })
                ->visible(fn(Payment $payment): bool => $payment->status === DataStatus::PENDING->value && $payment->payment_source === PaymentSource::PAYMENT_GATEWAY->value),

            Action::make('validation')
                ->label('Validasi Pembayaran')
                ->icon('heroicon-o-check-badge')
                ->requiresConfirmation()
                ->modalHeading('Validasi Pembayaran')
                ->modalDescription('Apakah yakin akan validasi pembayaran? Pastikan total pembayaran dengan buktinya sesuai!')
                ->form([
                    TextInput::make('password')
                        ->label('Password')
                        ->required()
                        ->password()
                        ->revealable()
                        ->placeholder('Masukkan Password Akun Anda')
                ])
                ->action(function (Payment $record, array $data) {
                    if (!Hash::check($data['password'], Auth::user()->password)) {
                        Notification::make()
                            ->title('Gagal memproses pembayaran')
                            ->body('Password salah!')
                            ->danger()
                            ->send();

                        return;
                    }

                    $record->status = DataStatus::PAID->value;
                    $record->save();

                    // Send notification to user
                    Notification::make()
                        ->title('Berhasil memproses pembayaran')
                        ->body('Pembayaran berhasil divalidasi!.')
                        ->success()
                        ->send();

                    Notification::make()
                        ->title('Berhasil memproses pembayaran')
                        ->body('Pembayaran anda berhasil divalidasi!.')
                        ->success()
                        ->sendToDatabase($record->user);
                })
                ->visible(fn(Payment $payment): bool => !Auth::user()->hasRole('user') && $payment->status === DataStatus::CONFIRMED->value)
        ];
    }
}
