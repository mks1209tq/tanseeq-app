<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection('clipboard')->create('clipboard_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('title')->nullable();
            $table->text('content');
            $table->string('type')->default('text'); // text, url, code, etc.
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->index('user_id');
            $table->index(['user_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('clipboard')->dropIfExists('clipboard_items');
    }
};
