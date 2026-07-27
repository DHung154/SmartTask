<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TeamInvitationTest extends TestCase
{
    use RefreshDatabase;

    private function makeTeam(User $owner): Team
    {
        $team = Team::create([
            'name'        => 'Nhóm đồ án',
            'description' => 'Test',
            'owner_id'    => $owner->id,
        ]);

        TeamMember::create([
            'team_id' => $team->id,
            'user_id' => $owner->id,
            'role'    => 'owner',
        ]);

        return $team;
    }

    public function test_invite_creates_pending_invitation_and_sends_mail_without_adding_member(): void
    {
        $owner = User::factory()->create();
        $invitee = User::factory()->create();
        $team = $this->makeTeam($owner);

        $this->actingAs($owner)
            ->post('/teams/add-member', [
                'team_id' => $team->id,
                'email'   => $invitee->email,
                'role'    => 'member',
            ])
            ->assertRedirect('/teams/detail?id=' . $team->id);

        $this->assertDatabaseHas('team_invitations', [
            'team_id' => $team->id,
            'user_id' => $invitee->id,
            'status'  => 'pending',
            'role'    => 'member',
        ]);

        // Điểm mấu chốt: chưa được thêm vào nhóm khi chưa chấp nhận.
        $this->assertDatabaseMissing('team_members', [
            'team_id' => $team->id,
            'user_id' => $invitee->id,
        ]);

        // MAIL_MAILER=array trong phpunit.xml nên email nằm lại ở transport.
        $messages = Mail::mailer()->getSymfonyTransport()->messages();
        $this->assertCount(1, $messages);

        $sent = $messages[0]->getOriginalMessage();
        $this->assertSame($invitee->email, $sent->getTo()[0]->getAddress());
        $this->assertStringContainsString($team->name, $sent->getSubject());
    }

    public function test_accepting_invitation_adds_member(): void
    {
        $owner = User::factory()->create();
        $invitee = User::factory()->create();
        $team = $this->makeTeam($owner);

        $invitation = TeamInvitation::create([
            'team_id'    => $team->id,
            'user_id'    => $invitee->id,
            'invited_by' => $owner->id,
            'role'       => 'admin',
            'status'     => 'pending',
        ]);

        $this->actingAs($invitee)
            ->post('/invitations/accept', ['id' => $invitation->id])
            ->assertRedirect('/teams/detail?id=' . $team->id);

        $this->assertDatabaseHas('team_members', [
            'team_id' => $team->id,
            'user_id' => $invitee->id,
            'role'    => 'admin',
        ]);

        $this->assertDatabaseHas('team_invitations', [
            'id'     => $invitation->id,
            'status' => 'accepted',
        ]);
    }

    public function test_declining_invitation_does_not_add_member(): void
    {
        $owner = User::factory()->create();
        $invitee = User::factory()->create();
        $team = $this->makeTeam($owner);

        $invitation = TeamInvitation::create([
            'team_id'    => $team->id,
            'user_id'    => $invitee->id,
            'invited_by' => $owner->id,
            'role'       => 'member',
            'status'     => 'pending',
        ]);

        $this->actingAs($invitee)->post('/invitations/decline', ['id' => $invitation->id]);

        $this->assertDatabaseMissing('team_members', [
            'team_id' => $team->id,
            'user_id' => $invitee->id,
        ]);

        $this->assertDatabaseHas('team_invitations', [
            'id'     => $invitation->id,
            'status' => 'declined',
        ]);
    }

    public function test_user_cannot_respond_to_someone_elses_invitation(): void
    {
        $owner = User::factory()->create();
        $invitee = User::factory()->create();
        $stranger = User::factory()->create();
        $team = $this->makeTeam($owner);

        $invitation = TeamInvitation::create([
            'team_id'    => $team->id,
            'user_id'    => $invitee->id,
            'invited_by' => $owner->id,
            'role'       => 'member',
            'status'     => 'pending',
        ]);

        $this->actingAs($stranger)->post('/invitations/accept', ['id' => $invitation->id]);

        $this->assertDatabaseMissing('team_members', [
            'team_id' => $team->id,
            'user_id' => $stranger->id,
        ]);

        $this->assertDatabaseHas('team_invitations', [
            'id'     => $invitation->id,
            'status' => 'pending',
        ]);
    }

    public function test_bell_shows_invitation_and_overdue_task(): void
    {
        $owner = User::factory()->create();
        $invitee = User::factory()->create();
        $team = $this->makeTeam($owner);

        TeamInvitation::create([
            'team_id'    => $team->id,
            'user_id'    => $invitee->id,
            'invited_by' => $owner->id,
            'role'       => 'member',
            'status'     => 'pending',
        ]);

        Task::create([
            'user_id'   => $invitee->id,
            'title'     => 'Việc trễ hạn cần nhắc',
            'due_date'  => today()->subDays(3),
            'completed' => false,
        ]);

        $response = $this->actingAs($invitee)->get('/tasks');

        $response->assertOk()
            ->assertSee('notifBell', false)
            ->assertSee($team->name)
            ->assertSee('Việc trễ hạn cần nhắc')
            ->assertSee('/invitations/accept', false)
            ->assertSee('/invitations/decline', false);
    }

    public function test_duplicate_invitation_is_rejected(): void
    {
        $owner = User::factory()->create();
        $invitee = User::factory()->create();
        $team = $this->makeTeam($owner);

        $payload = ['team_id' => $team->id, 'email' => $invitee->email, 'role' => 'member'];

        $this->actingAs($owner)->post('/teams/add-member', $payload);
        $this->actingAs($owner)->post('/teams/add-member', $payload);

        $this->assertSame(1, TeamInvitation::where('team_id', $team->id)->count());
    }
}
