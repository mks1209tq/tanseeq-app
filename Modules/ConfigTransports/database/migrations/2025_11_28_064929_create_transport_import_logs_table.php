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
        Schema::connection('config_transports')->create('transport_import_logs', function (Blueprint $table) {
            $table->id();
            $table->string('transport_number');
            $table->string('import_environment');
            $table->foreignId('imported_by')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->string('status'); // success, partial, failed
            $table->json('summary')->nullable();
            $table->timestamps();

            $table->index('transport_number');
            $table->index('import_environment');
            $table->index('imported_by');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('config_transports')->dropIfExists('transport_import_logs');
    }
};
