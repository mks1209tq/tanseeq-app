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
        Schema::connection('authorization')->create('authorization_failures', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('auth_object_code');
            $table->json('required_fields');
            $table->json('summary')->nullable();
            $table->boolean('is_allowed')->default(false);
            $table->string('route_name')->nullable();
            $table->string('request_path')->nullable();
            $table->string('request_method')->nullable();
            $table->string('client_ip')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            // Indexes for efficient queries
            $table->index(['user_id', 'created_at']);
            $table->index('auth_object_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('authorization')->dropIfExists('authorization_failures');
    }
};

