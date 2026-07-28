<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private function teamWith(User $owner, User ...$members): Team
    {
        $team = Team::create(['name' => 'Nhóm đồ án', 'owner_id' => $owner->id]);
        TeamMember::create(['team_id' => $team->id, 'user_id' => $owner->id, 'role' => 'owner']);

        foreach ($members as $member) {
            TeamMember::create(['team_id' => $team->id, 'user_id' => $member->id, 'role' => 'member']);
        }

        return $team;
    }

    public function test_owner_can_assign_task_to_team_member(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = $this->teamWith($owner, $member);

        $this->actingAs($owner)->post('/tasks/create', [
            'title'       => 'Viết chương 3',
            'team_id'     => (string) $team->id,
            'assignee_id' => $member->id,
            'priority'    => 'high',
            'status'      => 'todo',
        ]);

        $this->assertDatabaseHas('tasks', [
            'title'       => 'Viết chương 3',
            'user_id'     => $owner->id,
            'assignee_id' => $member->id,
            'team_id'     => $team->id,
        ]);
    }

    public function test_cannot_assign_to_user_outside_the_team(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $team = $this->teamWith($owner);

        $this->actingAs($owner)->post('/tasks/create', [
            'title'       => 'Việc không được giao',
            'team_id'     => (string) $team->id,
            'assignee_id' => $stranger->id,
            'priority'    => 'normal',
        ])->assertSessionHasErrors('assignee_id');

        $this->assertDatabaseMissing('tasks', ['title' => 'Việc không được giao']);
    }

    public function test_cannot_assign_when_task_is_personal(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $this->teamWith($owner, $member);

        $this->actingAs($owner)->post('/tasks/create', [
            'title'       => 'Việc cá nhân',
            'team_id'     => '',
            'assignee_id' => $member->id,
            'priority'    => 'normal',
        ])->assertSessionHasErrors('assignee_id');
    }

    public function test_assigned_task_appears_in_assignee_task_list(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = $this->teamWith($owner, $member);

        Task::create([
            'user_id'     => $owner->id,
            'assignee_id' => $member->id,
            'team_id'     => $team->id,
            'title'       => 'Việc giao cho thành viên',
        ]);

        $this->actingAs($member)->get('/tasks?filter=assigned-to-me')
            ->assertOk()
            ->assertSee('Việc giao cho thành viên');
    }

    public function test_assigned_by_me_filter_excludes_own_tasks(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = $this->teamWith($owner, $member);

        Task::create([
            'user_id' => $owner->id, 'assignee_id' => $member->id,
            'team_id' => $team->id, 'title' => 'Đã giao đi',
        ]);
        Task::create([
            'user_id' => $owner->id, 'assignee_id' => $owner->id,
            'team_id' => $team->id, 'title' => 'Tự làm',
        ]);

        $response = $this->actingAs($owner)->get('/tasks?filter=assigned-by-me');

        $response->assertOk()->assertSee('Đã giao đi')->assertDontSee('Tự làm');
    }

    public function test_assignment_sends_email_and_shows_in_assignee_bell(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = $this->teamWith($owner, $member);

        $this->actingAs($owner)->post('/tasks/create', [
            'title'       => 'Chuẩn bị slide',
            'team_id'     => (string) $team->id,
            'assignee_id' => $member->id,
            'priority'    => 'normal',
        ]);

        $messages = \Illuminate\Support\Facades\Mail::mailer()->getSymfonyTransport()->messages();
        $this->assertCount(1, $messages);
        $this->assertSame($member->email, $messages[0]->getOriginalMessage()->getTo()[0]->getAddress());

        $this->actingAs($member)->get('/tasks')
            ->assertOk()
            ->assertSee('Chuẩn bị slide');
    }

    public function test_member_cannot_assign_task_to_admin_or_others(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = $this->teamWith($owner, $member);

        $this->actingAs($member)->post('/tasks/create', [
            'title'       => 'Công việc do member tạo',
            'team_id'     => (string) $team->id,
            'assignee_id' => $owner->id,
            'priority'    => 'normal',
        ]);

        $this->assertDatabaseHas('tasks', [
            'title'       => 'Công việc do member tạo',
            'user_id'     => $member->id,
            'assignee_id' => null,
            'team_id'     => $team->id,
        ]);
    }

    public function test_member_can_update_progress_and_save(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = $this->teamWith($owner, $member);

        $task = Task::create([
            'user_id'     => $owner->id,
            'assignee_id' => $member->id,
            'team_id'     => $team->id,
            'title'       => 'Việc cần làm',
            'progress'    => 0,
            'status'      => 'todo',
        ]);

        $this->actingAs($member)->post('/tasks/update', [
            'id'          => $task->id,
            'title'       => 'Việc cần làm',
            'team_id'     => (string) $team->id,
            'assignee_id' => $owner->id, // Member cố gắng đổi assignee sang owner
            'progress'    => 50,
            'status'      => 'doing',
            'priority'    => 'normal',
        ])->assertRedirect('/');

        $this->assertDatabaseHas('tasks', [
            'id'          => $task->id,
            'assignee_id' => $member->id, // Vẫn giữ nguyên assignee là member
            'progress'    => 50,
            'status'      => 'doing',
        ]);
    }
}
