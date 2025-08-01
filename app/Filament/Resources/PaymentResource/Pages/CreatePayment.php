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

    /**
     * @param array $data
     * @return array
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $invoicePayments = $data['invoicePayments'] ?? [];
        $totalApplied = collect($invoicePayments)->sum('amount_applied');
        $amount = $data['amount'] ?? 0;

        // Validasi: Total Amount tidak boleh kurang dari jumlah Amount Applied
        if ($amount < $totalApplied) {
            throw ValidationException::withMessages([
                'amount' => 'Total Amount tidak boleh kurang dari jumlah Amount Applied pada invoice.',
            ]);
        }

        // Validasi per invoicePayment
        foreach ($invoicePayments as $index => $row) {
            $invoiceId = $row['invoice_id'] ?? null;
            $amountApplied = $row['amount_applied'] ?? 0;
            if ($invoiceId) {
                $invoice = Invoice::with(['invoiceItems', 'invoicePayments'])->find($invoiceId);
                if ($invoice) {
                    $outstanding = $invoice->invoiceItems->sum('rate') - $invoice->invoicePayments->sum('amount_applied');
                    // Perlu mengakomodir skenario create: amount_applied yang sedang diinput belum ada di DB
                    // Jadi, pada create, outstanding + amount_applied (dari input) >= amount_applied (dari input)
                    if ($amountApplied > ($outstanding + $amountApplied)) {
                        throw ValidationException::withMessages([
                            "invoicePayments.$index.amount_applied" => "Amount Applied untuk invoice " . $invoice->code . " tidak boleh melebihi Outstanding.",
                        ]);
                    }
                    // Namun, pada create, amount_applied tidak boleh lebih dari outstanding
                    if ($amountApplied > $outstanding) {
                        throw ValidationException::withMessages([
                            "invoicePayments.$index.amount_applied" => "Amount Applied untuk invoice " . $invoice->code . " tidak boleh melebihi Outstanding.",
                        ]);
                    }
                }
            }
        }

        return $data;
    }
}