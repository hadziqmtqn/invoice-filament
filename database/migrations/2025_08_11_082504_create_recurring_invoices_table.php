<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('recurring_invoices', function (Blueprint $table) {
            $table->id();
            $table->uuid('slug');
            $table->string('invoice_number');
            $table->integer('serial_number')->unique();
            $table->string('code')->unique();
            $table->unsignedBigInteger('user_id');
            $table->string('title');
            $table->date('date');
            $table->date('due_date')->nullable();
            $table->string('recurrence_frequency');
            $table->integer('repeat_every');
            $table->float('discount')->nullable();
            $table->text('note')->nullable();
            $table->enum('status', ['draft', 'active', 'discontinued'])->default('draft');
            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_invoices');
    }
};
