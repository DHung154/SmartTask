<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('list_id')->nullable()->constrained('lists')->nullOnDelete();
            $table->foreignId('team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->string('attachment_path', 255)->nullable();
            $table->string('attachment_name', 255)->nullable();
            $table->boolean('completed')->default(false);
            $table->boolean('is_important')->default(false);
            $table->string('priority', 20)->default('normal');
            $table->unsignedTinyInteger('progress')->default(0);
            $table->date('due_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->timestamp('reminder_sent_at')->nullable();
            $table->timestamp('reminder_queued_at')->nullable();

            // Indexes
            $table->index(['user_id', 'deleted_at'], 'idx_tasks_user_deleted');
            $table->index('due_date', 'idx_tasks_due_date');
            $table->index('priority', 'idx_tasks_priority');
            $table->index(['user_id', 'progress', 'deleted_at'], 'idx_tasks_progress');
            $table->index(['completed', 'deleted_at', 'due_date', 'reminder_sent_at'], 'idx_tasks_reminder');
            $table->index('team_id', 'idx_tasks_team_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
