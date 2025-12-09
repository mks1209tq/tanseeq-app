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
        // Drop existing table
        Schema::connection('authentication')->dropIfExists('auth_settings');
        
        // Recreate with simplified structure: key, value, description, timestamps only
        Schema::connection('authentication')->create('auth_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->index('key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('authentication')->dropIfExists('auth_settings');
        
        // Restore original structure with type field
        Schema::connection('authentication')->create('auth_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('boolean');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->index('key');
        });
    }
};
