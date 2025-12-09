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
        Schema::connection('authorization')->create('privileged_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('role_type'); // 'SuperAdmin' or 'SuperReadOnly'
            $table->string('auth_object_code');
            $table->string('activity_code')->nullable(); // ACTVT code (e.g., '01', '02', '03')
            $table->json('required_fields')->nullable();
            $table->string('route_name')->nullable();
            $table->string('request_path');
            $table->string('request_method');
            $table->json('request_data')->nullable(); // POST/PUT data if applicable
            $table->string('client_ip')->nullable();
            $table->text('user_agent')->nullable();
            $table->text('notes')->nullable(); // Additional context
            $table->timestamps();

            // Indexes for efficient queries
            $table->index(['user_id', 'created_at']);
            $table->index(['role_type', 'created_at']);
            $table->index('auth_object_code');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('authorization')->dropIfExists('privileged_activity_logs');
    }
};
