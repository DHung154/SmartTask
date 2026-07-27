<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('path', 255);
            $table->string('name', 255);
            $table->unsignedInteger('size')->default(0);
            $table->timestamps();

            $table->index('task_id', 'idx_attachments_task');
        });

        // Chuyển file đính kèm đơn lẻ đang có sang bảng mới.
        $existing = DB::table('tasks')
            ->whereNotNull('attachment_path')
            ->where('attachment_path', '!=', '')
            ->get(['id', 'user_id', 'attachment_path', 'attachment_name']);

        foreach ($existing as $task) {
            DB::table('task_attachments')->insert([
                'task_id'    => $task->id,
                'user_id'    => $task->user_id,
                'path'       => $task->attachment_path,
                'name'       => $task->attachment_name ?: basename($task->attachment_path),
                'size'       => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('task_attachments');
    }
};
