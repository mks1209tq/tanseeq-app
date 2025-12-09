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
        Schema::connection('authorization')->create('role_authorizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('roles')->onDelete('cascade');
            $table->foreignId('auth_object_id')->constrained('auth_objects')->onDelete('cascade');
            $table->string('label')->nullable();
            $table->timestamps();

            $table->index('role_id');
            $table->index('auth_object_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('authorization')->dropIfExists('role_authorizations');
    }
};
