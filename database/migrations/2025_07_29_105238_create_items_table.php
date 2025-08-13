<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->uuid('slug');
            $table->integer('serial_number')->unique();
            $table->string('code')->unique();
            $table->enum('product_type', ['goods', 'service']);
            $table->string('name');
            $table->string('item_name');
            $table->string('unit')->nullable();
            $table->decimal('rate', 20, 0);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
