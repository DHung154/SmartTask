<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class KanbanRepeatAttachmentTest extends TestCase
{
    use RefreshDatabase;

    // ---------- Kanban / trạng thái ----------

    public function test_drag_to_column_updates_status_and_returns_json(): void
    {
        $user = User::factory()->create();
        $task = Task::create(['user_id' => $user->id, 'title' => 'Kéo thả', 'status' => 'todo']);

        $response = $this->actingAs($user)
            ->postJson('/tasks/status', ['id' => $task->id, 'status' => 'review']);

        $response->assertOk()->assertJsonPath('data.status', 'review');

        $task->refresh();
        $this->assertSame('review', $task->status);
        $this->assertFalse((bool) $task->completed);
    }

    public function test_moving_to_done_marks_completed(): void
    {
        $user = User::factory()->create();
        $task = Task::create(['user_id' => $user->id, 'title' => 'Xong việc', 'status' => 'doing', 'progress' => 40]);

        $this->actingAs($user)->postJson('/tasks/status', ['id' => $task->id, 'status' => 'done']);

        $task->refresh();
        $this->assertTrue((bool) $task->completed);
        $this->assertSame(100, (int) $task->progress);
    }

    public function test_stranger_cannot_change_status(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $task = Task::create(['user_id' => $owner->id, 'title' => 'Riêng tư', 'status' => 'todo']);

        $this->actingAs($stranger)
            ->postJson('/tasks/status', ['id' => $task->id, 'status' => 'done'])
            ->assertNotFound();

        $this->assertSame('todo', $task->fresh()->status);
    }

    public function test_kanban_page_shows_four_columns(): void
    {
        $user = User::factory()->create();
        Task::create(['user_id' => $user->id, 'title' => 'Việc chờ duyệt', 'status' => 'review']);

        $this->actingAs($user)->get('/kanban')
            ->assertOk()
            ->assertSee('data-status="todo"', false)
            ->assertSee('data-status="doing"', false)
            ->assertSee('data-status="review"', false)
            ->assertSee('data-status="done"', false)
            ->assertSee('Việc chờ duyệt');
    }

    // ---------- Lặp lại ----------

    public function test_completing_repeating_task_spawns_next_occurrence(): void
    {
        $user = User::factory()->create();
        $task = Task::create([
            'user_id'  => $user->id,
            'title'    => 'Họp nhóm hằng tuần',
            'due_date' => today()->toDateString(),
            'repeat'   => 'weekly',
        ]);

        $this->actingAs($user)->post('/tasks/toggle', ['id' => $task->id]);

        $next = Task::where('repeat_parent_id', $task->id)->first();

        $this->assertNotNull($next, 'Phải sinh ra lần kế tiếp');
        $this->assertSame(today()->addWeek()->toDateString(), $next->due_date->toDateString());
        $this->assertSame('weekly', $next->repeat);
        $this->assertFalse((bool) $next->completed);
    }

    public function test_repeat_stops_after_repeat_until(): void
    {
        $user = User::factory()->create();
        $task = Task::create([
            'user_id'      => $user->id,
            'title'        => 'Việc lặp có hạn',
            'due_date'     => today()->toDateString(),
            'repeat'       => 'weekly',
            'repeat_until' => today()->addDays(3)->toDateString(),
        ]);

        $this->actingAs($user)->post('/tasks/toggle', ['id' => $task->id]);

        $this->assertSame(0, Task::where('repeat_parent_id', $task->id)->count());
    }

    public function test_repeat_requires_due_date(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/tasks/create', [
            'title'    => 'Lặp không hạn',
            'repeat'   => 'daily',
            'priority' => 'normal',
        ])->assertSessionHasErrors('repeat');
    }

    public function test_recurring_command_backfills_missed_occurrence(): void
    {
        $user = User::factory()->create();
        Task::create([
            'user_id'  => $user->id,
            'title'    => 'Việc lặp bị bỏ quên',
            'due_date' => today()->subDays(2)->toDateString(),
            'repeat'   => 'daily',
        ]);

        $this->artisan('tasks:generate-recurring')->assertSuccessful();

        $this->assertSame(1, Task::where('title', 'Việc lặp bị bỏ quên')
            ->whereNotNull('repeat_parent_id')->count());
    }

    // ---------- File đính kèm ----------

    public function test_can_upload_multiple_attachments(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/tasks/create', [
            'title'       => 'Việc có file',
            'priority'    => 'normal',
            'attachments' => [
                UploadedFile::fake()->create('bao-cao.pdf', 40, 'application/pdf'),
                UploadedFile::fake()->create('so-lieu.xlsx', 25),
            ],
        ]);

        $task = Task::where('title', 'Việc có file')->first();

        $this->assertNotNull($task);
        $this->assertSame(2, $task->attachments()->count());

        // Dọn file thật đã ghi ra public/uploads/tasks.
        foreach ($task->attachments as $attachment) {
            @unlink($attachment->absolutePath());
        }
    }

    public function test_removing_attachment_deletes_file_from_disk(): void
    {
        $user = User::factory()->create();
        $task = Task::create(['user_id' => $user->id, 'title' => 'Việc có file']);

        $dir = public_path('uploads/tasks');
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        $filename = 'test_' . bin2hex(random_bytes(4)) . '.txt';
        file_put_contents($dir . DIRECTORY_SEPARATOR . $filename, 'noi dung');

        $attachment = TaskAttachment::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'path'    => '/uploads/tasks/' . $filename,
            'name'    => 'ghi-chu.txt',
            'size'    => 8,
        ]);

        $this->assertFileExists($attachment->absolutePath());

        $this->actingAs($user)->post('/tasks/attachment/remove', ['attachment_id' => $attachment->id]);

        $this->assertDatabaseMissing('task_attachments', ['id' => $attachment->id]);
        $this->assertFileDoesNotExist($dir . DIRECTORY_SEPARATOR . $filename);
    }

    public function test_attachment_limit_is_enforced(): void
    {
        $user = User::factory()->create();

        $files = [];
        for ($i = 0; $i < 6; $i++) {
            $files[] = UploadedFile::fake()->create("file{$i}.txt", 5);
        }

        $this->actingAs($user)->post('/tasks/create', [
            'title'       => 'Quá nhiều file',
            'priority'    => 'normal',
            'attachments' => $files,
        ])->assertSessionHasErrors('attachment');

        $this->assertDatabaseMissing('tasks', ['title' => 'Quá nhiều file']);
    }
}
