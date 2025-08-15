<?php

namespace App\Filament\Resources\PaymentResource\Pages;

use App\Enums\InvoiceStatus;
use App\Filament\Resources\InvoiceResource;
use App\Filament\Resources\PaymentResource;
use App\Filament\Resources\UserResource;
use App\Models\Payment;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\FontWeight;
use Illuminate\Support\HtmlString;

class ViewPayment extends ViewRecord
{
    protected static string $resource = PaymentResource::class;

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
                                TextEntry::make('user.name')
                                    ->label('User Name')
                                    ->weight(FontWeight::Bold)
                                    ->url(fn(Payment $record): string => UserResource::getUrl('edit', ['record' => $record->user?->username]))
                                    ->color('primary'),

                                TextEntry::make('user.userProfile.company_name')
                                    ->label('Company')
                                    ->weight(FontWeight::Bold),

                                TextEntry::make('user.userProfile.phone')
                                    ->label('Phone')
                                    ->weight(FontWeight::Bold),

                                TextEntry::make('user.email')
                                    ->label('Email')
                                    ->weight(FontWeight::Bold),
                            ]),

                        Section::make('Invoices')
                            ->schema([
                                RepeatableEntry::make('invoicePayments')
                                    ->hiddenLabel()
                                    ->columns()
                                    ->schema([
                                        TextEntry::make('invoice.code')
                                            ->label('Code')
                                            ->inlineLabel(),

                                        TextEntry::make('invoice.total_price')
                                            ->label('Total Price')
                                            ->inlineLabel()
                                            ->money('idr'),

                                        TextEntry::make('invoice.status')
                                            ->label('Status')
                                            ->inlineLabel()
                                            ->badge()
                                            ->color(fn($state): string => InvoiceStatus::tryFrom($state)?->getColor() ?? 'gray')
                                            ->formatStateUsing(fn($state): string => InvoiceStatus::tryFrom($state)?->getLabel() ?? 'N/A'),

                                        RepeatableEntry::make('invoice.invoiceItems')
                                            ->label('Items')
                                            ->columns(3)
                                            ->columnSpan(2)
                                            ->schema([
                                                TextEntry::make('name'),

                                                TextEntry::make('qty'),

                                                TextEntry::make('rate')
                                                    ->money('idr'),

                                                TextEntry::make('description')
                                                    ->columnSpanFull()
                                            ]),

                                        TextEntry::make('invoice.note')
                                            ->label('Note')
                                            ->columnSpan(2),

                                        TextEntry::make('invoice.slug')
                                            ->hiddenLabel()
                                            ->inlineLabel()
                                            ->columnSpan(2)
                                            ->formatStateUsing(function ($state) {
                                                $url = InvoiceResource::getUrl('view', ['record' => $state]);
                                                $button = '<a href="' . $url . '" target="_blank" rel="noopener" class="inline-block bg-primary-600 text-white rounded px-3 py-1 hover:bg-primary-700 transition text-sm">Lihat</a>';
                                                return new HtmlString($button);
                                            }),
                                    ])
                            ])
                    ])
                    ->columnSpan(['lg' => 2])
            ])
                ->columns(3);
    }
}
