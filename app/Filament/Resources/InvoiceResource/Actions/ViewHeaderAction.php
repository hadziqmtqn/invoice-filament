<?php

namespace App\Filament\Resources\InvoiceResource\Actions;

use App\Enums\DataStatus;
use App\Filament\Resources\InvoiceResource;
use App\Jobs\UnpaidBillMessageJob;
use App\Models\Application;
use App\Models\Invoice;
use App\Services\CreatePaymentService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Torgodly\Html2Media\Actions\Html2MediaAction;

class ViewHeaderAction
{
    public static function headerActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('manual_payment')
                    ->label('Bayar Manual')
                    ->icon('heroicon-o-banknotes')
                    ->form([
                        Section::make()
                            ->columns()
                            ->schema([
                                DatePicker::make('date')
                                    ->label('Tanggal Bayar')
                                    ->placeholder('Masukkan tanggal pembayaran')
                                    ->native(false)
                                    ->required()
                                    ->minDate(fn(Invoice $invoice): string => $invoice->date)
                                    ->closeOnDateSelection(),

                                TextInput::make('amount')
                                    ->label('Jumlah Bayar')
                                    ->placeholder('Masukkan jumlah bayar')
                                    ->required()
                                    ->numeric()
                                    ->minValue(10000)
                                    ->maxValue(fn(Invoice $invoice): int => $invoice->total_due)
                            ])
                    ])
                    ->closeModalByClickingAway(false),

                Action::make('pay')
                    ->label('Payment Gateway')
                    ->icon('heroicon-o-currency-dollar')
                    ->requiresConfirmation()
                    ->modalDescription('Apakah yakin akan bayar sekarang?')
                    ->modalIconColor('danger')
                    ->modalWidth('sm')
                    ->form([
                        TextInput::make('amount')
                            ->label('Nominal Bayar')
                            ->numeric()
                            ->required()
                            ->minValue(10000)
                            ->default(fn (Invoice $record) => $record->invoicePaymentPending?->payment?->amount ?? $record->total_due)
                            ->maxValue(fn (Invoice $record) => $record->total_due)
                            ->readOnly(fn (Invoice $record): bool => $record->invoicePaymentPending?->payment?->amount ?? false)
                            ->prefix('Rp'),
                    ])
                    ->action(function (Invoice $record, array $data, $livewire) {
                        $amount = $record->invoicePaymentPending?->payment?->amount && $record->invoicePaymentPending?->payment?->amount != $data['amount'] ? $record->invoicePaymentPending?->payment?->amount : $data['amount'];
                        $snapToken = CreatePaymentService::handle($record, $amount);

                        session()->flash('snapToken', $snapToken);

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
                    ->after(function (Invoice $record, array $data, $livewire) {
                        // Dipanggil setelah action selesai dan modal tertutup
                        if (session()->has('snapToken')) {
                            $livewire->dispatch('midtrans-pay', session('snapToken'));
                        }
                    })
                    ->visible(fn(Invoice $invoice): bool => $invoice->status !== DataStatus::DRAFT->value && $invoice->total_due > 0),
            ])
                ->label('Bayar Sekarang')
                ->icon('heroicon-o-currency-dollar')
                ->button(),

            ActionGroup::make([
                Html2MediaAction::make('download')
                    ->label('Unduh')
                    ->color('info')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->content(fn(Invoice $record): View => view('filament.resources.invoice-resource.print', [
                        'invoice' => $record->loadMissing('invoiceItems'),
                        'application' => Application::first()
                    ]))
                    ->filename(fn(Invoice $record) => $record->code . '-' . $record->invoice_number . '.pdf')
                    ->preview()
                    ->savePdf(),

                Action::make('send_invoice')
                    ->label('Kirim Faktur')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->action(function (Invoice $record) {
                        if ($record->status === 'draft' || $record->status === 'sent' || $record->status === 'unpaid') {
                            UnpaidBillMessageJob::dispatch([
                                'user_name' => $record->user?->name ?? 'Unknown User',
                                'invoice_name' => $record->title,
                                'amount' => 'Rp' . number_format($record->total_price,0,',','.'),
                                'due_date' => $record->due_date?->format('d M Y') ?? now()->format('d M Y'),
                                'whatsapp_number' => $record->user?->userProfile?->phone ?? '',
                                'invoice_id' => $record->id,
                            ]);

                            Notification::make()
                                ->success()
                                ->title('Faktur Terkirim')
                                ->body('Faktur berhasil terkirim.')
                                ->send();

                            Notification::make()
                                ->success()
                                ->title('Fakur Baru')
                                ->body('Anda memiliki tagihan baru dengan nomor penagihan: ' . $record->code)
                                ->sendToDatabase($record->user)
                                ->actions([
                                    Action::make('Lihat Faktur')
                                        ->url(InvoiceResource::getUrl('view', ['record' => $record->slug])),
                                ]);
                        }
                    })
                    ->visible(fn(Invoice $record): bool => !auth()->user()->hasRole('user') && ($record->status === 'draft' || $record->status === 'sent' || $record->status === 'partially_paid' || $record->status === 'unpaid'))
            ])
                ->label('Opsi')
                ->button()
                ->color('warning')
        ];
    }
}
