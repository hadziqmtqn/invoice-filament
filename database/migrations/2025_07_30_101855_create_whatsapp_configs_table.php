<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('whatsapp_configs', function (Blueprint $table) {
            $table->id();
            $table->uuid('slug');
            $table->enum('provider', ['wablas', 'wanesia', 'fonnte']);
            $table->string('api_domain');
            $table->string('secret_key')->nullable();
            $table->string('api_key');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_configs');
    }
};
