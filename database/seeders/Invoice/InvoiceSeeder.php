<?php

namespace Database\Seeders\Invoice;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoicePayment;
use App\Models\Item;
use App\Models\Payment;
use Faker\Factory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class InvoiceSeeder extends Seeder
{
    public function run(): void
    {
        $item = Item::pluck('id');
        $faker = Factory::create();

        Invoice::factory(300)
            ->create()
            ->each(function (Invoice $invoice) use ($item, $faker) {
                $itemSelected = Item::find($item->random());

                // Invoice Item
                $invoiceItem = new InvoiceItem();
                $invoiceItem->invoice_id = $invoice->id;
                $invoiceItem->item_id = $itemSelected->id;
                $invoiceItem->name = $itemSelected->name;
                $invoiceItem->qty = 1;
                $invoiceItem->rate = $itemSelected->rate;
                $invoiceItem->save();
            });

        $invoices = Invoice::all();

        foreach ($invoices as $invoice) {
            $totalPaid = $invoice->invoiceItems->sum('rate');
            // Payment
            $payment = new Payment();
            $payment->user_id = $invoice->user_id;
            $payment->date = Carbon::parse($invoice->date)->addDays(3);
            $payment->amount = $totalPaid;
            $payment->payment_method = $faker->randomElement(['cash', 'qris', 'bank_transfer']);
            $payment->payment_channel = $payment->payment_method == 'qris' ? $faker->randomElement(['gopay', 'dana', 'qris', 'ovo']) : null;
            $payment->payment_source = $payment->payment_method == 'cash' ? 'cash' : 'payment_gateway';
            $payment->transaction_time = Carbon::parse($invoice->date)->addDays(3);
            $payment->status = 'paid';
            $payment->save();

            // invoice payment
            $invoicePayment = new InvoicePayment();
            $invoicePayment->payment_id = $payment->id;
            $invoicePayment->invoice_id = $invoice->id;
            $invoicePayment->amount_applied = $totalPaid;
            $invoicePayment->save();
        }
    }
}
