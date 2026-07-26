<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_queue', function (Blueprint $table) {
            $table->id();
            $table->string('type', 80);
            $table->json('payload');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('available_at')->useCurrent();
            $table->timestamp('reserved_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('error_message', 1000)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['type', 'status', 'available_at'], 'idx_job_queue_next');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_queue');
    }
};
