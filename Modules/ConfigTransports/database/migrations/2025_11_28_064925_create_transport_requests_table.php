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
        Schema::connection('config_transports')->create('transport_requests', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->string('type'); // security, config, master_data, mixed
            $table->string('status')->default('open'); // open, released, exported, imported, failed
            $table->string('source_environment');
            $table->json('target_environments')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->foreignId('released_by')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->timestamp('released_at')->nullable();
            $table->timestamps();

            $table->index('number');
            $table->index('status');
            $table->index('type');
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('config_transports')->dropIfExists('transport_requests');
    }
};
