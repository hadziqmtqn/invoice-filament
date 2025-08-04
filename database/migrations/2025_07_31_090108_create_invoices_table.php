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
            $table->string('title');
            $table->unsignedBigInteger('user_id');
            $table->date('date');
            $table->date('due_date');
            $table->float('discount')->default(0);
            $table->text('note')->nullable();
            $table->enum('status', ['draft', 'sent', 'paid', 'overdue', 'unpaid', 'partially_paid'])->default('draft');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
