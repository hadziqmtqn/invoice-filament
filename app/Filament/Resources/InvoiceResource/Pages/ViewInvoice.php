<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Filament\Resources\RecurringInvoiceResource\Pages\ViewRecurringInvoice;
use App\Filament\Resources\UserResource;
use App\Jobs\UnpaidBillMessageJob;
use App\Models\Application;
use App\Models\Invoice;
use App\Traits\HasMidtransSnap;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Infolists\Components\Actions;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\FontWeight;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;
use Torgodly\Html2Media\Actions\Html2MediaAction;

class ViewInvoice extends ViewRecord
{
    use HasMidtransSnap;

    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('pay')
                ->label('Pay Now')
                ->icon('heroicon-o-currency-dollar')
                ->requiresConfirmation()
                ->modalHeading('Pay Now')
                ->action(function (Invoice $record, $livewire) {
                    if (!$record->midtrans_snap_token) {
                        $params = [
                            'transaction_details' => [
                                'order_id' => $record->id,
                                'gross_amount' => $record->total_price,
                            ],
                            'customer_details' => [
                                'first_name' => $record->user?->name,
                                'email' => $record->user?->email,
                                'phone' => $record->user?->userProfile?->phone,
                            ],
                            'item_details' => $record->invoiceItems->map(function ($detail) {
                                return [
                                    'id' => $detail->id,
                                    'name' => $detail->name,
                                    'price' => $detail->rate,
                                    'quantity' => $detail->qty,
                                ];
                            })->toArray(),
                            'callbacks' => [
                                'finish' => InvoiceResource::getUrl('view', ['record' => $record]),
                            ],
                            // customer_details, dst
                        ];

                        $snapToken = $this->generateMidtransSnapToken($params);

                        $record->midtrans_snap_token = $snapToken;
                        $record->save();
                    }else {
                        $snapToken = $record->midtrans_snap_token;
                    }

                    $livewire->dispatch('midtrans-pay', $snapToken);
                }),

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
                        Log::info('Sending invoice for record: ' . $record->code);
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
                                    ->size(TextEntry\TextEntrySize::Large)
                                    ->weight(FontWeight::Bold)
                                    ->color('primary'),
                            ]),
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
                                    ->numeric(decimalPlaces: 2)
                                    ->inlineLabel(),

                                TextEntry::make('payment.payment_method')
                                    ->label('Payment Method')
                                    ->weight('bold')
                                    ->formatStateUsing(fn(string $state): string => ucfirst(str_replace('_', ' ', $state)))
                                    ->inlineLabel(),
                            ])
                            ->columns()
                    ])
            ])
            ->columns(3);
    }
}
