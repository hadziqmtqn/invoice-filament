<?php

namespace App\Filament\Resources\PaymentResource\Pages;

use App\Enums\DataStatus;
use App\Enums\PaymentSource;
use App\Filament\Resources\InvoiceResource;
use App\Filament\Resources\PaymentResource;
use App\Filament\Resources\UserResource;
use App\Models\Invoice;
use App\Models\Payment;
use Filament\Actions\Action;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\IconPosition;
use Illuminate\Support\HtmlString;

class ViewPayment extends ViewRecord
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
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
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        parent::infolist($infolist);

        return $infolist
            ->schema([
                Group::make()
                    ->schema([
                        Section::make()
                            ->columns()
                            ->inlineLabel()
                            ->schema([
                                TextEntry::make('reference_number')
                                    ->label('No. Ref')
                                    ->weight(FontWeight::Bold),

                                TextEntry::make('date')
                                    ->label('Tanggal')
                                    ->date()
                                    ->weight(FontWeight::Bold),

                                TextEntry::make('user.name')
                                    ->label('Pengguna')
                                    ->weight(FontWeight::Bold)
                                    ->url(fn(Payment $record): string => UserResource::getUrl('edit', ['record' => $record->user?->username]))
                                    ->icon('heroicon-o-arrow-top-right-on-square')
                                    ->iconPosition(IconPosition::After)
                                    ->color('primary'),

                                TextEntry::make('user.userProfile.company_name')
                                    ->label('Tempat Usaha')
                                    ->weight(FontWeight::Bold),

                                TextEntry::make('user.userProfile.phone')
                                    ->label('No. HP')
                                    ->weight(FontWeight::Bold),

                                TextEntry::make('user.email')
                                    ->label('Email')
                                    ->weight(FontWeight::Bold),
                            ]),

                        Section::make('Faktur')
                            ->schema([
                                RepeatableEntry::make('invoicePayments')
                                    ->hiddenLabel()
                                    ->columns()
                                    ->schema([
                                        TextEntry::make('invoice.title')
                                            ->label('Judul')
                                            ->columnSpanFull(),

                                        TextEntry::make('invoice.slug')
                                            ->label('Kode')
                                            ->inlineLabel()
                                            ->formatStateUsing(function ($state): ?HtmlString {
                                                $invoice = Invoice::where('slug', $state)
                                                    ->first();
                                                if ($invoice) {
                                                    $url = InvoiceResource::getUrl('view', ['record' => $state]);
                                                    return new HtmlString('<a href="' . $url . '" target="_blank" rel="noopener" class="text-primary-400">'. $invoice->code .'</a>');
                                                }

                                                return null;
                                            })
                                            ->icon('heroicon-o-arrow-top-right-on-square')
                                            ->iconPosition(IconPosition::After),

                                        TextEntry::make('invoice.total_price')
                                            ->label('Total Tagihan')
                                            ->inlineLabel()
                                            ->money('idr'),

                                        TextEntry::make('invoice.status')
                                            ->label('Status')
                                            ->inlineLabel()
                                            ->badge()
                                            ->color(fn($state): string => DataStatus::tryFrom($state)?->getColor() ?? 'gray')
                                            ->formatStateUsing(fn($state): string => DataStatus::tryFrom($state)?->getLabel() ?? 'N/A'),

                                        RepeatableEntry::make('invoice.invoiceItems')
                                            ->label('Item')
                                            ->columns(3)
                                            ->columnSpan(2)
                                            ->schema([
                                                TextEntry::make('name'),

                                                TextEntry::make('qty'),

                                                TextEntry::make('rate')
                                                    ->label('Sub Total')
                                                    ->money('idr'),

                                                TextEntry::make('description')
                                                    ->label('Deskripsi')
                                                    ->columnSpanFull()
                                            ]),

                                        TextEntry::make('invoice.note')
                                            ->label('Note')
                                            ->columnSpan(2),
                                    ])
                            ]),
                    ])
                    ->columnSpan(['lg' => 2]),

                Group::make()
                    ->schema([
                        Section::make()
                            ->schema([
                                TextEntry::make('status')
                                    ->formatStateUsing(fn($state): string => DataStatus::tryFrom($state)?->getLabel() ?? 'N/A')
                                    ->color(fn($state): string => DataStatus::tryFrom($state)?->getColor() ?? 'gray')
                                    ->weight(FontWeight::Bold)
                                    ->size(TextEntry\TextEntrySize::Large),

                                TextEntry::make('payment_source')
                                    ->label('Payment Source')
                                    ->formatStateUsing(fn($state): string => PaymentSource::tryFrom($state)?->getLabel() ?? 'N/A')
                                    ->weight(FontWeight::Bold),

                                TextEntry::make('payment_method')
                                    ->label('Payment Method')
                                    ->formatStateUsing(fn($state): string => strtoupper($state))
                                    ->weight(FontWeight::Bold),

                                TextEntry::make('bankAccount.name')
                                    ->label('Bank Account')
                                    ->visible(fn(Payment $record): bool => $record->payment_source === PaymentSource::BANK_TRANSFER->value)
                                    ->weight(FontWeight::Bold),

                                TextEntry::make('amount')
                                    ->money('idr')
                                    ->size(TextEntry\TextEntrySize::Large)
                                    ->weight(FontWeight::Bold)
                                    ->color('primary')
                            ]),

                        Section::make('Note')
                            ->schema([
                                TextEntry::make('note')
                                    ->hiddenLabel()
                            ]),

                        Section::make('Attachment')
                            ->schema([
                                TextEntry::make('payment_attachment')
                                    ->hiddenLabel()
                                    ->formatStateUsing(function ($state) {
                                        $data = @json_decode($state);
                                        // dd($data->mimeType);

                                        if (!is_object($data) || empty($data->fileUri)) {
                                            return '-';
                                        }

                                        $url = $data->fileUri;
                                        $mime = $data->mimeType;
                                        $originalName = $data->originalName ?? 'Lihat File';

                                        if (str_starts_with($mime, 'image/')) {
                                            return new HtmlString('<img src="' . $url . '" alt="Attachment" class="w-24 h-24 object-cover rounded">');
                                        }

                                        if ($mime === 'application/pdf') {
                                            return new HtmlString('<a href="' . $url . '" target="_blank" rel="noopener" class="inline-block bg-primary-600 text-white rounded px-3 py-1 hover:bg-primary-700 transition text-sm">Show PDF</a>');
                                        }

                                        // Untuk file lain, tampilkan tombol download dengan nama file
                                        return new HtmlString('<a href="' . $url . '" target="_blank" class="inline-block bg-gray-500 text-white rounded px-2 py-1 text-sm hover:bg-gray-600 transition">Download ' . e($originalName) . '</a>');
                                    })
                                    ->html()
                            ])
                    ])
                    ->columnSpan(['lg' => 1]),
            ])
                ->columns(3);
    }
}
