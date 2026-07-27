<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Trong cùng một test, Laravel giữ lại guard đã resolve nên request sau
     * vẫn nhận user của request trước. Gọi hàm này giữa các request để
     * mỗi lần gọi API đều xác thực lại từ đầu bằng token gửi kèm.
     */
    private function forgetAuth(): void
    {
        $this->app['auth']->forgetGuards();
    }

    private function tokenFor(User $user): string
    {
        $response = $this->postJson('/api/v1/login', [
            'email'    => $user->email,
            'password' => 'password',
        ]);

        $response->assertOk();

        return $response->json('data.token');
    }

    public function test_login_returns_token(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/v1/login', ['email' => $user->email, 'password' => 'password'])
            ->assertOk()
            ->assertJsonStructure(['data' => ['token', 'user' => ['id', 'name', 'email', 'role']]]);
    }

    public function test_login_rejects_wrong_password(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/v1/login', ['email' => $user->email, 'password' => 'sai-mat-khau'])
            ->assertStatus(401);
    }

    public function test_protected_routes_require_token(): void
    {
        $this->getJson('/api/v1/tasks')->assertStatus(401);
        $this->getJson('/api/v1/summary')->assertStatus(401);
    }

    public function test_full_task_crud_over_api(): void
    {
        $user = User::factory()->create();
        $token = $this->tokenFor($user);
        $headers = ['Authorization' => 'Bearer ' . $token];

        // Create
        $created = $this->postJson('/api/v1/tasks', [
            'title'    => 'Việc từ API',
            'priority' => 'high',
            'due_date' => today()->addDay()->toDateString(),
        ], $headers)->assertCreated();

        $taskId = $created->json('data.id');

        // Read
        $this->getJson('/api/v1/tasks/' . $taskId, $headers)
            ->assertOk()
            ->assertJsonPath('data.title', 'Việc từ API');

        // Update
        $this->putJson('/api/v1/tasks/' . $taskId, ['title' => 'Đã đổi tên', 'status' => 'done'], $headers)
            ->assertOk()
            ->assertJsonPath('data.title', 'Đã đổi tên')
            ->assertJsonPath('data.status', 'done');

        $this->assertTrue((bool) Task::find($taskId)->completed);

        // Delete (soft delete)
        $this->deleteJson('/api/v1/tasks/' . $taskId, [], $headers)->assertOk();
        $this->assertSoftDeleted('tasks', ['id' => $taskId]);
    }

    public function test_api_validation_errors(): void
    {
        $user = User::factory()->create();
        $headers = ['Authorization' => 'Bearer ' . $this->tokenFor($user)];

        $this->postJson('/api/v1/tasks', ['title' => ''], $headers)
            ->assertStatus(422)
            ->assertJsonPath('errors.title', 'Title is required.');

        $this->postJson('/api/v1/tasks', ['title' => 'X', 'status' => 'khong-hop-le'], $headers)
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['status']]);
    }

    public function test_user_cannot_touch_another_users_task(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $task = Task::create(['user_id' => $owner->id, 'title' => 'Riêng tư']);

        $headers = ['Authorization' => 'Bearer ' . $this->tokenFor($stranger)];
        $this->forgetAuth();

        $this->getJson('/api/v1/tasks/' . $task->id, $headers)->assertNotFound();
        $this->putJson('/api/v1/tasks/' . $task->id, ['title' => 'Đổi trộm'], $headers)->assertNotFound();
        $this->deleteJson('/api/v1/tasks/' . $task->id, [], $headers)->assertNotFound();

        $this->assertSame('Riêng tư', $task->fresh()->title);
    }

    public function test_summary_reports_status_breakdown(): void
    {
        $user = User::factory()->create();
        Task::create(['user_id' => $user->id, 'title' => 'A', 'status' => 'todo']);
        Task::create(['user_id' => $user->id, 'title' => 'B', 'status' => 'done', 'completed' => true]);

        $headers = ['Authorization' => 'Bearer ' . $this->tokenFor($user)];

        $this->getJson('/api/v1/summary', $headers)
            ->assertOk()
            ->assertJsonPath('data.total', 2)
            ->assertJsonPath('data.completed', 1)
            ->assertJsonPath('data.by_status.todo', 1)
            ->assertJsonPath('data.by_status.done', 1);
    }

    public function test_admin_endpoint_blocks_regular_user(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $userToken = $this->tokenFor($user);
        $this->forgetAuth();
        $adminToken = $this->tokenFor($admin);
        $this->forgetAuth();

        $this->getJson('/api/v1/admin/summary', ['Authorization' => 'Bearer ' . $userToken])
            ->assertStatus(403);

        $this->forgetAuth();

        $this->getJson('/api/v1/admin/summary', ['Authorization' => 'Bearer ' . $adminToken])
            ->assertOk()
            ->assertJsonStructure(['data' => ['users', 'tasks']]);
    }

    public function test_logout_revokes_token(): void
    {
        $user = User::factory()->create();
        $headers = ['Authorization' => 'Bearer ' . $this->tokenFor($user)];

        $this->forgetAuth();
        $this->getJson('/api/v1/me', $headers)->assertOk();

        $this->forgetAuth();
        $this->postJson('/api/v1/logout', [], $headers)->assertOk();

        $this->forgetAuth();
        $this->getJson('/api/v1/me', $headers)->assertStatus(401);
    }
}
