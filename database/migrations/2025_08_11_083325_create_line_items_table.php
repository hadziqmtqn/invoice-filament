<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('line_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('recurring_invoice_id');
            $table->unsignedBigInteger('item_id');
            $table->string('name');
            $table->integer('qty');
            $table->string('unit')->nullable();
            $table->decimal('rate');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->foreign('recurring_invoice_id')
                ->references('id')
                ->on('recurring_invoices')
                ->cascadeOnDelete();

            $table->foreign('item_id')
                ->references('id')
                ->on('items')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('line_items');
    }
};
