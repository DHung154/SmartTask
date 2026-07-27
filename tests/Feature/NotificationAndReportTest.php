<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationAndReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_feed_returns_counts_and_sections(): void
    {
        $owner = User::factory()->create();
        $me = User::factory()->create();

        $team = Team::create(['name' => 'Nhóm A', 'owner_id' => $owner->id]);
        TeamMember::create(['team_id' => $team->id, 'user_id' => $owner->id, 'role' => 'owner']);

        TeamInvitation::create([
            'team_id' => $team->id, 'user_id' => $me->id,
            'invited_by' => $owner->id, 'role' => 'member', 'status' => 'pending',
        ]);

        Task::create([
            'user_id' => $me->id, 'title' => 'Trễ hạn',
            'due_date' => today()->subDays(2), 'completed' => false,
        ]);
        Task::create([
            'user_id' => $me->id, 'title' => 'Hôm nay', 'due_date' => today(), 'completed' => false,
        ]);
        Task::create([
            'user_id' => $owner->id, 'assignee_id' => $me->id,
            'title' => 'Được giao', 'completed' => false,
        ]);

        $response = $this->actingAs($me)->getJson('/notifications/feed');

        $response->assertOk()
            ->assertJsonPath('count', 4)
            ->assertJsonCount(1, 'invitations')
            ->assertJsonCount(1, 'overdue')
            ->assertJsonCount(1, 'due_today')
            ->assertJsonCount(1, 'assigned')
            ->assertJsonPath('invitations.0.team', 'Nhóm A')
            ->assertJsonPath('overdue.0.title', 'Trễ hạn')
            ->assertJsonPath('assigned.0.title', 'Được giao');
    }

    public function test_notification_feed_requires_login(): void
    {
        $this->get('/notifications/feed')->assertRedirect('/login');
    }

    public function test_bell_shows_assigned_task_section(): void
    {
        $owner = User::factory()->create();
        $me = User::factory()->create();

        Task::create([
            'user_id' => $owner->id, 'assignee_id' => $me->id,
            'title' => 'Việc sếp giao', 'completed' => false,
        ]);

        $this->actingAs($me)->get('/tasks')
            ->assertOk()
            ->assertSee('Việc sếp giao')
            ->assertSee('notif-icon-assign', false);
    }

    public function test_report_page_renders_charts_with_data(): void
    {
        $user = User::factory()->create();

        Task::create(['user_id' => $user->id, 'title' => 'A', 'priority' => 'high', 'status' => 'todo']);
        Task::create(['user_id' => $user->id, 'title' => 'B', 'priority' => 'low', 'status' => 'done', 'completed' => true]);

        $response = $this->actingAs($user)->get('/report');

        $response->assertOk()
            ->assertSee('report-chart-data', false)
            ->assertSee('priorityChart', false)
            ->assertSee('statusChart', false)
            ->assertSee('monthlyChart', false)
            ->assertSee('chart.js', false);
    }

    public function test_report_page_hides_charts_when_no_tasks(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/report')
            ->assertOk()
            ->assertDontSee('priorityChart', false)
            ->assertSee('Chưa có công việc nào để thống kê');
    }

    public function test_csv_export_includes_new_columns(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::create(['name' => 'Nhóm CSV', 'owner_id' => $owner->id]);
        TeamMember::create(['team_id' => $team->id, 'user_id' => $owner->id, 'role' => 'owner']);
        TeamMember::create(['team_id' => $team->id, 'user_id' => $member->id, 'role' => 'member']);

        Task::create([
            'user_id' => $owner->id, 'assignee_id' => $member->id, 'team_id' => $team->id,
            'title' => 'Việc xuất CSV', 'status' => 'review', 'repeat' => 'weekly',
            'due_date' => today(),
        ]);

        $response = $this->actingAs($owner)->get('/report/export.csv');
        $response->assertOk();

        $csv = $response->streamedContent();

        $this->assertStringContainsString('Giao cho', $csv);
        $this->assertStringContainsString($member->name, $csv);
        $this->assertStringContainsString('Chờ duyệt', $csv);
        $this->assertStringContainsString('Hằng tuần', $csv);
    }

    public function test_sidebar_shows_assignment_section_when_relevant(): void
    {
        $owner = User::factory()->create();
        $me = User::factory()->create();

        Task::create(['user_id' => $owner->id, 'assignee_id' => $me->id, 'title' => 'Giao việc']);

        $this->actingAs($me)->get('/tasks')
            ->assertOk()
            ->assertSee('filter=assigned-to-me', false);
    }
}
