<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('invoice_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('payment_id');
            $table->unsignedBigInteger('invoice_id');
            $table->decimal('amount_applied', 20, 0);
            $table->timestamps();

            $table->foreign('payment_id')->references('id')->on('payments')->cascadeOnDelete();
            $table->foreign('invoice_id')->references('id')->on('invoices')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_payments');
    }
};
