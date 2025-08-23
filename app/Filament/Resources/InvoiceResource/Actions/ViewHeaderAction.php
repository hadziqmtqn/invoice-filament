<?php

namespace App\Filament\Resources\InvoiceResource\Actions;

use App\Enums\DataStatus;
use App\Enums\PaymentSource;
use App\Enums\RecurrenceFrequency;
use App\Enums\RecurringInvoiceStatus;
use App\Filament\Resources\InvoiceResource;
use App\Jobs\UnpaidBillMessageJob;
use App\Models\Application;
use App\Models\BankAccount;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Payment;
use App\Services\CreatePaymentService;
use App\Services\RecurringInvoiceService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Support\Enums\IconPosition;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;
use Torgodly\Html2Media\Actions\Html2MediaAction;

class ViewHeaderAction
{
    public static function headerActions(): array
    {
        return [
            ActionGroup::make([
                // TODO Manual Payment
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
                                    ->default(now())
                                    ->minDate(fn(Invoice $invoice): string => $invoice->date)
                                    ->closeOnDateSelection()
                                    ->prefixIcon('heroicon-o-calendar'),

                                TextInput::make('amount')
                                    ->label('Jumlah Bayar')
                                    ->placeholder('Masukkan jumlah bayar')
                                    ->required()
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->minValue(10000)
                                    ->maxValue(fn(Invoice $invoice): int => $invoice->total_due)
                                    ->default(fn(Invoice $invoice): int => $invoice->total_due)
                                    ->hintIcon('heroicon-o-information-circle', 'Anda dapat memasukkan nominal minimal Rp10.000'),

                                ToggleButtons::make('payment_source')
                                    ->label('Sumber Pembayaran')
                                    ->inline()
                                    ->options(PaymentSource::options())
                                    ->colors(PaymentSource::colors())
                                    ->required()
                                    ->reactive(),

                                Select::make('bank_account_id')
                                    ->label('Bank Tujuan')
                                    ->options(BankAccount::with('bank')->where('is_active', true)->get()->pluck('bank.short_name', 'id')->toArray())
                                    ->native(false)
                                    ->required(fn(Get $get): bool => $get('payment_source') === PaymentSource::BANK_TRANSFER->value)
                                    ->visible(fn(Get $get): bool => $get('payment_source') === PaymentSource::BANK_TRANSFER->value),
                            ]),

                        Section::make('Bukti Pembayaran')
                            ->schema([
                                FileUpload::make('attachment')
                                    ->label('Bukti Pembayaran')
                                    ->hiddenLabel()
                                    ->disk('local')
                                    ->directory('payment_file_temp')
                                    ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                                    ->required(),
                            ]),

                        Section::make('Status')
                            ->schema([
                                ToggleButtons::make('status')
                                    ->hiddenLabel()
                                    ->label('Status')
                                    ->required()
                                    ->inline()
                                    ->options(DataStatus::options(['pending', 'paid']))
                                    ->colors(DataStatus::colors(['pending', 'paid'])),
                            ])
                            ->visible(fn(): bool => Auth::user()->hasRole('super_admin'))
                    ])
                    ->action(function (Invoice $invoice, array $data): void {
                        if ($invoice->status === DataStatus::CONFIRMED->value) {
                            Notification::make()
                                ->warning()
                                ->title('Faktur telah dikonfirmasi')
                                ->body('Tidak dapat mengkonfirmasi ulang pembayaran.')
                                ->send();

                            return;
                        }

                        DB::transaction(function () use ($invoice, $data) {
                            $payment = new Payment();
                            $payment->user_id = $invoice->user_id;
                            $payment->date = $data['date'];
                            $payment->amount = $data['amount'];
                            $payment->payment_source = $data['payment_source'];
                            $payment->bank_account_id = !empty($data['bank_account_id']) ? $data['bank_account_id'] : null;
                            $payment->status = !empty($data['status']) ? $data['status'] : DataStatus::CONFIRMED->value;
                            $payment->save();

                            $payment->addMedia(Storage::disk('local')->path($data['attachment']))
                                ->toMediaCollection('payment_attachments');

                            $invoicePayment = new InvoicePayment();
                            $invoicePayment->payment_id = $payment->id;
                            $invoicePayment->invoice_id = $invoice->id;
                            $invoicePayment->amount_applied = $data['amount'];
                            $invoicePayment->save();

                            // Update Invoice
                            $invoice->status = DataStatus::CONFIRMED->value;
                            $invoice->save();
                        });
                    })
                    ->closeModalByClickingAway(false)
                    ->visible(fn(Invoice $invoice): bool => !collect([DataStatus::DRAFT->value, DataStatus::CONFIRMED->value])->contains($invoice->status) && $invoice->total_due > 0),

                // TODO Payment Gateway
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
                        if ($record->status === DataStatus::CONFIRMED->value) {
                            Notification::make()
                                ->warning()
                                ->title('Faktur telah dikonfirmasi')
                                ->body('Tidak dapat mengkonfirmasi ulang pembayaran.')
                                ->send();

                            return;
                        }

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
                    ->visible(fn(Invoice $invoice): bool => !collect([DataStatus::DRAFT->value, DataStatus::CONFIRMED->value])->contains($invoice->status) && $invoice->total_due > 0),
            ])
                ->label('Bayar Sekarang')
                ->icon('heroicon-o-currency-dollar')
                ->button(),

            // TODO More Options
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
                        if (in_array($record->status, [DataStatus::DRAFT->value, DataStatus::SENT->value, DataStatus::UNPAID->value])) {
                            UnpaidBillMessageJob::dispatch([
                                'user_name' => $record->user?->name ?? 'Unknown User',
                                'invoice_name' => $record->title,
                                'amount' => number_format($record->total_price,0,',','.'),
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
                                ->title('Faktur Baru')
                                ->body('Anda memiliki tagihan baru. <a href="' . InvoiceResource::getUrl('view', ['record' => $record->slug]) . '" class="text-primary underline">Lihat Faktur</a>')
                                ->sendToDatabase($record->user);
                        }
                    })
                    ->visible(fn(Invoice $record): bool => !auth()->user()->hasRole('user') && $record->status !== DataStatus::PAID->value),

                // TODO Conversion to Repeated Invoice
                Action::make('conversion')
                    ->label('Jadikan Faktur Berulang')
                    ->icon('heroicon-s-arrow-path')
                    ->color('warning')
                    ->visible(fn(Invoice $invoice): bool => $invoice->status !== DataStatus::DRAFT->value)
                    ->requiresConfirmation()
                    ->slideOver()
                    ->modalWidth('sm')
                    ->modalHeading('Faktur Berulang')
                    ->modalDescription('Isi data berikut untuk mengaktifkan faktur berulang')
                    ->form([
                        TextInput::make('title')
                            ->label('Judul Faktur')
                            ->required()
                            ->placeholder('Masukkan Judul Faktur')
                            ->default(fn(Invoice $invoice): string => $invoice->title)
                            ->minLength(5),

                        DateTimePicker::make('date')
                            ->label('Tanggal Mulai Berlaku')
                            ->required()
                            ->native(false)
                            ->minDate(fn(Invoice $invoice): string => $invoice->date)
                            ->default(fn(Invoice $record) => $record->date ?? now())
                            ->prefixIcon('heroicon-o-calendar')
                            ->closeOnDateSelection(),

                        Select::make('recurrence_frequency')
                            ->label('Frekuensi Perulangan')
                            ->options(RecurrenceFrequency::options())
                            ->required()
                            ->native(false),

                        TextInput::make('repeat_every')
                            ->label('Ulangi Setiap')
                            ->required()
                            ->integer()
                            ->default(1)
                            ->placeholder('Masukkan berapa kali untuk mengulangi'),

                        ToggleButtons::make('status')
                            ->label('Status')
                            ->required()
                            ->options(RecurringInvoiceStatus::options(['draft', 'active']))
                            ->colors(RecurringInvoiceStatus::colors())
                            ->inline()
                    ])
                    ->action(function (Invoice $record, array $data): void {
                        if ($record->recurringInvoice) {
                            Notification::make()
                                ->title('Faktur berulang sudah dibuat')
                                ->danger()
                                ->send();

                            return;
                        }

                        try {
                            RecurringInvoiceService::generateFromInvoice($record, $data);

                            Notification::make()
                                ->title('Faktur Berulang berhasil dibuat')
                                ->success()
                                ->send();
                        } catch (Throwable $throwable) {
                            Log::error($throwable->getMessage());
                            Notification::make()
                                ->title('Oops!')
                                ->body('Gagal menambahkan faktur berulang.')
                                ->danger()
                                ->send();
                        }
                    })
            ])
                ->label('Opsi Lainnya')
                ->button()
                ->color('warning')
                ->outlined()
                ->dropdownPlacement('bottom-end')
                ->icon('heroicon-m-chevron-down')
                ->iconPosition(IconPosition::After)
        ];
    }
}
