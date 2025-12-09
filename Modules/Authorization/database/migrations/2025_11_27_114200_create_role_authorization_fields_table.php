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
        Schema::connection('authorization')->create('role_authorization_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_authorization_id')->constrained('role_authorizations')->onDelete('cascade');
            $table->string('field_code');
            $table->string('operator')->default('=');
            $table->string('value_from')->nullable();
            $table->string('value_to')->nullable();
            $table->timestamps();

            $table->index(['role_authorization_id', 'field_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('authorization')->dropIfExists('role_authorization_fields');
    }
};
