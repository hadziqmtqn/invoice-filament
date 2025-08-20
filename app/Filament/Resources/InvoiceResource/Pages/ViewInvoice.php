<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Enums\DataStatus;
use App\Enums\PaymentSource;
use App\Filament\Resources\InvoiceResource;
use App\Filament\Resources\RecurringInvoiceResource\Pages\ViewRecurringInvoice;
use App\Filament\Resources\UserResource;
use App\Jobs\UnpaidBillMessageJob;
use App\Models\Application;
use App\Models\Invoice;
use App\Services\CreatePaymentService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\Actions;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\IconPosition;
use Illuminate\Contracts\View\View;
use Illuminate\Support\HtmlString;
use Torgodly\Html2Media\Actions\Html2MediaAction;

class ViewInvoice extends ViewRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('pay')
                ->label('Pay Now')
                ->icon('heroicon-o-currency-dollar')
                ->requiresConfirmation()
                ->modalDescription('Are you sure you will pay now?')
                ->modalIconColor('danger')
                ->modalWidth('sm')
                ->form([
                    TextInput::make('amount')
                        ->label('Nominal Pembayaran')
                        ->numeric()
                        ->required()
                        ->minValue(10000)
                        ->default(fn (Invoice $record) => $record->total_due)
                        ->maxValue(fn (Invoice $record) => $record->total_due)
                        ->prefix('Rp')
                        ->visible(fn(Invoice $invoice): bool => !$invoice->invoicePaymentPending),
                ])
                ->action(function (Invoice $record, array $data, $livewire) {
                    $record->refresh();
                    $amount = !empty($data['amount']) ? $data['amount'] : $record->invoicePaymentPending?->payment?->amount;
                    $snapToken = CreatePaymentService::handle($record, $amount);

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
                ->visible(fn(Invoice $invoice): bool => $invoice->status !== DataStatus::DRAFT->value),

            ActionGroup::make([
                Html2MediaAction::make('download')
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
                    ->label('Send Invoice')
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
                                ->title('Invoice Sent')
                                ->body('The invoice has been sent successfully.')
                                ->send();

                            Notification::make()
                                ->success()
                                ->title('New Invoice Sent')
                                ->body('You have a new bill with a billing number: ' . $record->code)
                                ->sendToDatabase($record->user)
                                ->actions([
                                    Actions\Action::make('View Invoice')
                                        ->url(InvoiceResource::getUrl('view', ['record' => $record->slug])),
                                ]);
                        }
                    })
                    ->visible(fn(Invoice $record): bool => !auth()->user()->hasRole('user') && ($record->status === 'draft' || $record->status === 'sent' || $record->status === 'partially_paid' || $record->status === 'unpaid'))
            ])
            ->label('More Actions')
            ->button()
            ->color('warning')
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        parent::infolist($infolist); // TODO: Change the autogenerated stub

        return $infolist
            ->schema([
                Group::make()
                    ->schema([
                        Section::make()
                            ->schema([
                                TextEntry::make('recurringInvoice.code')
                                    ->label('Recurring Invoice Code')
                                    ->color('primary')
                                    ->inlineLabel()
                                    ->url(fn(Invoice $record): string => $record->recurringInvoice ? ViewRecurringInvoice::getUrl(['record' => $record->recurringInvoice?->slug]) : '#'),

                                TextEntry::make('code')
                                    ->label('Invoice Code')
                                    ->color('primary')
                                    ->inlineLabel(),

                                TextEntry::make('title')
                                    ->label('Title')
                                    ->inlineLabel(),

                                TextEntry::make('user.name')
                                    ->label('User')
                                    ->url(fn(Invoice $record): string => UserResource::getUrl('edit', ['record' => $record->user?->username]))
                                    ->color('primary')
                                    ->icon('heroicon-o-arrow-top-right-on-square')
                                    ->iconPosition(IconPosition::After)
                                    ->inlineLabel(),

                                TextEntry::make('date')
                                    ->label('Date')
                                    ->date('d M Y')
                                    ->inlineLabel(),

                                TextEntry::make('due_date')
                                    ->label('Due Date')
                                    ->date('d M Y')
                                    ->inlineLabel(),
                            ]),

                        Section::make('Invoice Items')
                            ->schema([
                                RepeatableEntry::make('invoiceItems')
                                    ->hiddenLabel()
                                    ->columns()
                                    ->schema([
                                        TextEntry::make('item.name')
                                            ->label('Item Name')
                                            ->weight('bold')
                                            ->inlineLabel(),

                                        TextEntry::make('qty')
                                            ->label('Quantity')
                                            ->weight('bold')
                                            ->inlineLabel(),

                                        TextEntry::make('unit')
                                            ->label('Unit')
                                            ->weight('bold')
                                            ->inlineLabel(),

                                        TextEntry::make('rate')
                                            ->label('Rate')
                                            ->weight('bold')
                                            ->money('idr')
                                            ->prefix('Rp')
                                            ->numeric(0, ',', '.')
                                            ->inlineLabel()
                                            ->color('primary'),

                                        TextEntry::make('note')
                                            ->label('Note')
                                            ->weight('bold')
                                            ->inlineLabel(),
                                    ]),
                            ]),
                    ])
                    ->columnSpan(['lg' => 2]),

                Group::make()
                    ->schema([
                        Section::make()
                            ->schema([
                                TextEntry::make('status')
                                    ->color(fn(string $state): string => match ($state) {
                                        'draft' => 'gray',
                                        'sent' => 'primary',
                                        'paid' => 'success',
                                        'unpaid', 'overdue' => 'danger',
                                        'partially_paid' => 'warning',
                                        default => 'secondary',
                                    })
                                    ->formatStateUsing(fn(string $state): HtmlString => new HtmlString('<span class="text-xl font-semibold">' . str_replace('_', ' ', strtoupper($state)) . '</span>')),

                                TextEntry::make('total_price_before_discount')
                                    ->label('Total Price (Before Discount)')
                                    ->money('idr')
                                    ->size(TextEntry\TextEntrySize::Large)
                                    ->weight(FontWeight::Bold)
                                    ->visible(fn(Invoice $record): bool => $record->discount > 0),

                                TextEntry::make('total_price')
                                    ->label(fn(Invoice $record): string => $record->discount > 0 ? 'Total Price (After Discount)' : 'Total Price')
                                    ->money('idr')
                                    ->prefix('Rp')
                                    ->numeric(0, ',', '.')
                                    ->size(TextEntry\TextEntrySize::Large)
                                    ->weight(FontWeight::Bold)
                                    ->color('primary'),

                                TextEntry::make('total_paid')
                                    ->label('Total Paid')
                                    ->money('idr')
                                    ->prefix('Rp')
                                    ->numeric(0, ',', '.')
                                    ->size(TextEntry\TextEntrySize::Large)
                                    ->weight(FontWeight::Bold)
                                    ->color('info'),

                                TextEntry::make('total_due')
                                    ->label('Total Due')
                                    ->money('idr')
                                    ->prefix('Rp')
                                    ->numeric(0, ',', '.')
                                    ->size(TextEntry\TextEntrySize::Large)
                                    ->weight(FontWeight::Bold)
                                    ->color('danger'),
                            ]),

                        Actions::make([
                            Actions\Action::make('mark_as_sent')
                                ->color(Color::Indigo)
                                ->visible(fn(Invoice $record): bool => $record->status === DataStatus::DRAFT->value)
                                ->requiresConfirmation()
                                ->action(function (Invoice $record) {
                                    $record->status = DataStatus::SENT->value;
                                    $record->save();
                                })
                        ])
                    ])
                    ->columnSpan(['lg' => 1]),

                Section::make('Invoice Payments')
                    ->schema([
                        RepeatableEntry::make('invoicePayments')
                            ->hiddenLabel()
                            ->schema([
                                TextEntry::make('payment.reference_number')
                                    ->label('Reference Number')
                                    ->weight('bold')
                                    ->inlineLabel(),

                                TextEntry::make('payment.date')
                                    ->label('Date')
                                    ->date('d M Y')
                                    ->weight('bold')
                                    ->inlineLabel(),

                                TextEntry::make('payment.amount')
                                    ->label('Amount')
                                    ->weight('bold')
                                    ->money('idr')
                                    ->prefix('Rp')
                                    ->numeric(0, ',', '.')
                                    ->inlineLabel(),

                                TextEntry::make('payment.payment_source')
                                    ->label('Payment Source')
                                    ->weight('bold')
                                    ->formatStateUsing(fn($state): string => PaymentSource::tryFrom($state)?->getLabel() ?? 'N/A')
                                    ->inlineLabel(),

                                TextEntry::make('payment.payment_method')
                                    ->label('Payment Method')
                                    ->weight('bold')
                                    ->formatStateUsing(fn(string $state): string => strtoupper(str_replace('_', ' ', $state)))
                                    ->inlineLabel(),

                                TextEntry::make('payment.status')
                                    ->label('Status')
                                    ->weight('bold')
                                    ->formatStateUsing(fn($state): string => DataStatus::tryFrom($state)?->getLabel() ?? 'N/A')
                                    ->color(fn(string $state): string => DataStatus::tryFrom($state)?->getColor() ?? 'gray')
                                    ->inlineLabel(),
                            ])
                            ->columns()
                    ])
            ])
            ->columns(3);
    }
}
