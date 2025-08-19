<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->uuid('slug');
            $table->unsignedBigInteger('user_id');
            $table->integer('serial_number');
            $table->string('reference_number');
            $table->date('date');
            $table->decimal('amount', 20, 0);
            $table->string('midtrans_snap_token')->nullable();
            $table->string('payment_method')->nullable();
            $table->unsignedBigInteger('bank_account_id')->nullable();
            $table->string('payment_channel')->nullable();
            $table->string('payment_validation_status')->nullable();
            $table->string('payment_validation_note')->nullable();
            $table->timestamp('transaction_time')->nullable();
            $table->string('status')->default('PENDING');
            $table->text('note')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('bank_account_id')->references('id')->on('bank_accounts')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
