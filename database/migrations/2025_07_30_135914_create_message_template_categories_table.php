<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('message_template_categories', function (Blueprint $table) {
            $table->id();
            $table->uuid('slug');
            $table->string('code')->unique();
            $table->string('name');
            $table->string('placeholder')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_template_categories');
    }
};
