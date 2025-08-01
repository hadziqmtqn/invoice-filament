<?php

namespace App\Filament\Resources\PaymentResource\Pages;

use App\Filament\Resources\PaymentResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;
use App\Models\Invoice;

class CreatePayment extends CreateRecord
{
    protected static string $resource = PaymentResource::class;
    protected ?bool $hasDatabaseTransactions = true;

    protected function getHeaderActions(): array
    {
        return [

        ];
    }

    /** Validasi sebelum create */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $invoicePayments = $data['invoicePayments'] ?? [];
        $totalApplied = collect($invoicePayments)->sum('amount_applied');

        if ($data['amount'] > $totalApplied) {
            throw ValidationException::withMessages([
                'amount' => 'Total Amount tidak boleh lebih besar dari jumlah Amount Applied pada invoice.',
            ]);
        }

        // Validasi per invoicePayment
        foreach ($invoicePayments as $row) {
            $invoiceId = $row['invoice_id'] ?? null;
            $amountApplied = $row['amount_applied'] ?? 0;
            if ($invoiceId) {
                $invoice = Invoice::with(['invoiceItems', 'invoicePayments'])->find($invoiceId);
                if ($invoice) {
                    $outstanding = $invoice->invoiceItems->sum('rate') - $invoice->invoicePayments->sum('amount_applied');
                    if ($amountApplied > $outstanding) {
                        throw ValidationException::withMessages([
                            'invoicePayments' => "Jumlah Amount Applied untuk invoice " . $invoice->code . " tidak boleh melebihi Outstanding.",
                        ]);
                    }
                }
            }
        }
        return $data;
    }
}