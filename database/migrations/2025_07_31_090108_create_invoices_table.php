<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->uuid('slug');
            $table->string('invoice_number')->unique();
            $table->integer('serial_number')->unique();
            $table->string('code')->unique();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('recurring_invoice_id')->nullable();
            $table->string('title');
            $table->date('date');
            $table->date('due_date');
            $table->float('discount')->default(0);
            $table->text('note')->nullable();
            $table->enum('status', ['draft', 'sent', 'paid', 'overdue', 'unpaid', 'partially_paid'])->default('draft');
            $table->string('midtrans_snap_token')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('recurring_invoice_id')->references('id')->on('recurring_invoices')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
