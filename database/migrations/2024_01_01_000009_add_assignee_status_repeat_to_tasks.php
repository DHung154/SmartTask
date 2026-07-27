<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            // Người được giao việc. Khác user_id (người tạo).
            $table->foreignId('assignee_id')->nullable()->after('user_id')
                ->constrained('users')->nullOnDelete();

            // Trạng thái thật cho Kanban, thay vì suy ra từ progress.
            $table->enum('status', ['todo', 'doing', 'review', 'done'])
                ->default('todo')->after('completed');

            // Lặp lại định kỳ.
            $table->enum('repeat', ['none', 'daily', 'weekly', 'monthly'])
                ->default('none')->after('due_date');
            $table->date('repeat_until')->nullable()->after('repeat');
            $table->foreignId('repeat_parent_id')->nullable()->after('repeat_until')
                ->constrained('tasks')->nullOnDelete();

            $table->index(['assignee_id', 'deleted_at'], 'idx_tasks_assignee');
            $table->index(['user_id', 'status', 'deleted_at'], 'idx_tasks_status');
        });

        // Backfill status từ dữ liệu progress/completed đang có.
        DB::table('tasks')->where('completed', true)->update(['status' => 'done']);
        DB::table('tasks')->where('completed', false)->where('progress', '>', 0)->update(['status' => 'doing']);
        DB::table('tasks')->where('completed', false)->where('progress', 0)->update(['status' => 'todo']);
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['assignee_id']);
            $table->dropForeign(['repeat_parent_id']);
            $table->dropIndex('idx_tasks_assignee');
            $table->dropIndex('idx_tasks_status');
            $table->dropColumn(['assignee_id', 'status', 'repeat', 'repeat_until', 'repeat_parent_id']);
        });
    }
};
