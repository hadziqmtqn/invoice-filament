<?php

namespace App\Filament\Resources\PaymentResource\Pages;

use App\Filament\Resources\PaymentResource;
use App\Filament\Resources\UserResource;
use App\Models\Payment;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\FontWeight;

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
                            ])
                    ])
                    ->columnSpan(['lg' => 2])
            ])
                ->columns(3);
    }
}
