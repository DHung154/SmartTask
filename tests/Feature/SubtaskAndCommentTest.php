<?php

namespace Tests\Feature;

use App\Models\Subtask;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubtaskAndCommentTest extends TestCase
{
    use RefreshDatabase;

    private function task(User $user, array $overrides = []): Task
    {
        return Task::create($overrides + [
            'user_id' => $user->id,
            'title'   => 'Công việc lớn',
        ]);
    }

    public function test_adding_subtasks_drives_task_progress(): void
    {
        $user = User::factory()->create();
        $task = $this->task($user);

        $this->actingAs($user)->post('/subtasks/create', ['task_id' => $task->id, 'title' => 'Bước 1']);
        $this->actingAs($user)->post('/subtasks/create', ['task_id' => $task->id, 'title' => 'Bước 2']);

        $this->assertSame(2, $task->fresh()->subtasks()->count());
        $this->assertSame(0, (int) $task->fresh()->progress);

        $first = Subtask::where('task_id', $task->id)->orderBy('id')->first();
        $this->actingAs($user)->post('/subtasks/toggle', ['id' => $first->id]);

        $task->refresh();
        $this->assertSame(50, (int) $task->progress);
        $this->assertSame('doing', $task->status);
        $this->assertFalse((bool) $task->completed);
    }

    public function test_completing_all_subtasks_marks_task_done(): void
    {
        $user = User::factory()->create();
        $task = $this->task($user);

        foreach (['A', 'B'] as $title) {
            $this->actingAs($user)->post('/subtasks/create', ['task_id' => $task->id, 'title' => $title]);
        }

        foreach (Subtask::where('task_id', $task->id)->get() as $subtask) {
            $this->actingAs($user)->post('/subtasks/toggle', ['id' => $subtask->id]);
        }

        $task->refresh();
        $this->assertSame(100, (int) $task->progress);
        $this->assertSame('done', $task->status);
        $this->assertTrue((bool) $task->completed);

        // Bỏ tick một việc con thì task quay lại đang làm.
        $one = Subtask::where('task_id', $task->id)->first();
        $this->actingAs($user)->post('/subtasks/toggle', ['id' => $one->id]);

        $task->refresh();
        $this->assertSame('doing', $task->status);
        $this->assertFalse((bool) $task->completed);
    }

    public function test_manual_progress_is_ignored_when_subtasks_exist(): void
    {
        $user = User::factory()->create();
        $task = $this->task($user);

        $this->actingAs($user)->post('/subtasks/create', ['task_id' => $task->id, 'title' => 'Bước 1']);

        $this->actingAs($user)->post('/tasks/progress', [
            'id' => $task->id, 'progress' => 90,
        ]);

        $this->assertSame(0, (int) $task->fresh()->progress);
    }

    public function test_stranger_cannot_touch_subtasks(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $task = $this->task($owner);

        $subtask = Subtask::create(['task_id' => $task->id, 'title' => 'Bí mật']);

        $this->actingAs($stranger)->post('/subtasks/toggle', ['id' => $subtask->id]);

        $this->assertFalse((bool) $subtask->fresh()->completed);
    }

    public function test_user_can_comment_and_delete_own_comment(): void
    {
        $user = User::factory()->create();
        $task = $this->task($user);

        $this->actingAs($user)->post('/comments/create', [
            'task_id' => $task->id,
            'body'    => 'Nhớ kiểm tra lại số liệu.',
        ]);

        $this->assertDatabaseHas('task_comments', [
            'task_id' => $task->id,
            'user_id' => $user->id,
            'body'    => 'Nhớ kiểm tra lại số liệu.',
        ]);

        $comment = TaskComment::where('task_id', $task->id)->first();
        $this->actingAs($user)->post('/comments/delete', ['id' => $comment->id]);

        $this->assertDatabaseMissing('task_comments', ['id' => $comment->id]);
    }

    public function test_user_cannot_delete_someone_elses_comment(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $task = $this->task($owner);

        // Cho $other quyền truy cập task bằng cách giao việc cho họ.
        $task->update(['assignee_id' => $other->id]);

        $comment = TaskComment::create([
            'task_id' => $task->id,
            'user_id' => $owner->id,
            'body'    => 'Bình luận của chủ việc',
        ]);

        $this->actingAs($other)->post('/comments/delete', ['id' => $comment->id]);

        $this->assertDatabaseHas('task_comments', ['id' => $comment->id]);
    }

    public function test_comments_show_on_edit_page(): void
    {
        $user = User::factory()->create();
        $task = $this->task($user);

        TaskComment::create(['task_id' => $task->id, 'user_id' => $user->id, 'body' => 'Ghi chú kiểm thử']);
        Subtask::create(['task_id' => $task->id, 'title' => 'Việc con kiểm thử']);

        $this->actingAs($user)->get('/tasks/edit?id=' . $task->id)
            ->assertOk()
            ->assertSee('Ghi chú kiểm thử')
            ->assertSee('Việc con kiểm thử');
    }
}
