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
        Schema::connection('authorization')->create('auth_object_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('auth_object_id')->constrained('auth_objects')->onDelete('cascade');
            $table->string('code');
            $table->string('label')->nullable();
            $table->boolean('is_org_level')->default(false);
            $table->tinyInteger('sort')->default(0);
            $table->timestamps();

            $table->unique(['auth_object_id', 'code']);
            $table->index('auth_object_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('authorization')->dropIfExists('auth_object_fields');
    }
};
