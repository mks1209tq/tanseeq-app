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
        Schema::connection('authorization')->create('role_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('roles')->onDelete('cascade');
            // Remove foreign key to users (cross-database reference)
            // user_id is now just an integer reference to users in authentication database
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->unique(['role_id', 'user_id']);
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('authorization')->dropIfExists('role_user');
    }
};
