<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\TeamMember;
use App\Models\Task;
use App\Models\User;

class TeamController extends Controller
{
    public function index()
    {
        $userId = auth()->id();

        $teams = Team::select('teams.*')
            ->selectSub(function ($query) {
                $query->from('team_members')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('team_members.team_id', 'teams.id');
            }, 'total_members')
            ->addSelect('team_members.role as user_role')
            ->join('team_members', 'teams.id', '=', 'team_members.team_id')
            ->where('team_members.user_id', $userId)
            ->with('owner:id,name')
            ->orderByDesc('teams.created_at')
            ->get()
            ->map(function ($team) {
                $team->owner_name = $team->owner->name ?? '';
                return $team;
            });

        return view('teams.index', [
            'teams'       => $teams,
            'active_page' => 'teams',
        ]);
    }

    public function create()
    {
        return view('teams.create', [
            'active_page' => 'teams',
        ]);
    }

    public function store(Request $request)
    {
        $userId = auth()->id();
        $name = trim($request->input('name', ''));
        $description = trim($request->input('description', ''));

        if (empty($name)) {
            session()->flash('error', 'Tên nhóm không được để trống.');
            return redirect('/teams/create');
        }

        DB::beginTransaction();
        try {
            $team = Team::create([
                'name'        => $name,
                'description' => $description,
                'owner_id'    => $userId,
            ]);

            TeamMember::create([
                'team_id' => $team->id,
                'user_id' => $userId,
                'role'    => 'owner',
            ]);

            DB::commit();
            session()->flash('success', 'Đã tạo nhóm thành công!');
            return redirect('/teams/detail?id=' . $team->id);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('team.create_failed', ['message' => $e->getMessage()]);
            session()->flash('error', 'Tạo nhóm thất bại!');
            return redirect('/teams');
        }
    }

    public function detail(Request $request)
    {
        $userId = auth()->id();
        $teamId = (int) $request->query('id', 0);

        $myRole = TeamMember::where('team_id', $teamId)
            ->where('user_id', $userId)
            ->value('role');

        if (!$myRole) {
            abort(403, 'Bạn không phải là thành viên của nhóm này.');
        }

        $team = Team::findOrFail($teamId);

        $members = TeamMember::where('team_id', $teamId)
            ->join('users', 'team_members.user_id', '=', 'users.id')
            ->select('team_members.*', 'users.name', 'users.email')
            ->orderByRaw("FIELD(team_members.role, 'owner', 'admin', 'member')")
            ->get();

        $tasks = Task::where('team_id', $teamId)
            ->whereNull('deleted_at')
            ->join('users', 'tasks.user_id', '=', 'users.id')
            ->select('tasks.*', 'users.name as author_name')
            ->orderByDesc('tasks.created_at')
            ->get();

        $pendingInvitations = TeamInvitation::where('team_id', $teamId)
            ->where('status', 'pending')
            ->with('user:id,name,email')
            ->orderByDesc('created_at')
            ->get();

        return view('teams.detail', [
            'team'               => $team,
            'myRole'             => $myRole,
            'members'            => $members,
            'tasks'              => $tasks,
            'pendingInvitations' => $pendingInvitations,
            'active_page'        => 'teams',
        ]);
    }

    /**
     * Gửi lời mời vào nhóm. Người được mời chỉ trở thành thành viên
     * sau khi bấm chấp nhận ở chuông thông báo.
     */
    public function addMember(Request $request)
    {
        $userId = auth()->id();
        $teamId = (int) $request->input('team_id', 0);
        $email = trim($request->input('email', ''));
        $role = $request->input('role', 'member');

        $myRole = TeamMember::where('team_id', $teamId)
            ->where('user_id', $userId)
            ->value('role');

        if (!in_array($myRole, ['owner', 'admin'])) {
            session()->flash('error', 'Bạn không có quyền mời thành viên.');
            return redirect('/teams/detail?id=' . $teamId);
        }

        if (!in_array($role, ['member', 'admin'], true)) {
            $role = 'member';
        }

        $targetUser = User::where('email', $email)->first();
        if (!$targetUser) {
            session()->flash('error', 'Không tìm thấy người dùng có email này.');
            return redirect('/teams/detail?id=' . $teamId);
        }

        if ((int) $targetUser->id === (int) $userId) {
            session()->flash('error', 'Bạn đã ở trong nhóm này rồi.');
            return redirect('/teams/detail?id=' . $teamId);
        }

        $exists = TeamMember::where('team_id', $teamId)
            ->where('user_id', $targetUser->id)
            ->exists();

        if ($exists) {
            session()->flash('error', 'Người dùng đã là thành viên của nhóm.');
            return redirect('/teams/detail?id=' . $teamId);
        }

        $pending = TeamInvitation::where('team_id', $teamId)
            ->where('user_id', $targetUser->id)
            ->where('status', 'pending')
            ->exists();

        if ($pending) {
            session()->flash('error', 'Đã có lời mời đang chờ người này trả lời.');
            return redirect('/teams/detail?id=' . $teamId);
        }

        // updateOrCreate để mời lại được người đã từ chối trước đó.
        $invitation = TeamInvitation::updateOrCreate(
            ['team_id' => $teamId, 'user_id' => $targetUser->id],
            [
                'invited_by'   => $userId,
                'role'         => $role,
                'status'       => 'pending',
                'responded_at' => null,
            ]
        );

        $team = Team::find($teamId);
        $mailSent = true;
        try {
            Mail::send('emails.team-invitation', [
                'team'       => $team,
                'role'       => $role,
                'inviter'    => auth()->user(),
                'invitee'    => $targetUser,
                'invitation' => $invitation,
            ], function ($message) use ($email, $team) {
                $message->to($email)->subject('Lời mời tham gia nhóm ' . ($team->name ?? 'SmartTask'));
            });
        } catch (\Throwable $e) {
            $mailSent = false;
            Log::warning('team.invitation_email_failed', ['email' => $email, 'message' => $e->getMessage()]);
        }

        // Keep the existing custom queue record for compatibility with older workers.
        $payload = json_encode([
            'to_email' => $email,
            'subject'  => 'Bạn có một lời mời tham gia nhóm!',
            'body'     => "<h3>Lời Mời Nhóm</h3><p>Bạn được mời vào nhóm làm việc trên SmartTask với vai trò <b>{$role}</b>. Đăng nhập và mở chuông thông báo để trả lời.</p>",
        ]);
        DB::table('job_queue')->insert([
            'type'    => 'send_team_task_email',
            'payload' => $payload,
        ]);

        // Lời mời đã vào chuông thông báo dù email lỗi, nên vẫn báo thành công
        // nhưng nói rõ phần email chưa gửi được thay vì im lặng.
        session()->flash('success', $mailSent
            ? 'Đã gửi lời mời tới ' . $targetUser->name . '.'
            : 'Đã tạo lời mời cho ' . $targetUser->name . ' (email chưa gửi được — kiểm tra cấu hình MAIL_* trong .env).');

        return redirect('/teams/detail?id=' . $teamId);
    }

    /**
     * Hủy lời mời chưa được trả lời (owner/admin).
     */
    public function cancelInvitation(Request $request)
    {
        $userId = auth()->id();
        $teamId = (int) $request->input('team_id', 0);
        $invitationId = (int) $request->input('id', 0);

        $myRole = TeamMember::where('team_id', $teamId)
            ->where('user_id', $userId)
            ->value('role');

        if (!in_array($myRole, ['owner', 'admin'])) {
            session()->flash('error', 'Bạn không có quyền hủy lời mời.');
            return redirect('/teams/detail?id=' . $teamId);
        }

        $deleted = TeamInvitation::where('id', $invitationId)
            ->where('team_id', $teamId)
            ->where('status', 'pending')
            ->delete();

        session()->flash($deleted ? 'success' : 'error', $deleted
            ? 'Đã hủy lời mời.'
            : 'Không tìm thấy lời mời đang chờ.');

        return redirect('/teams/detail?id=' . $teamId);
    }

    public function destroy(Request $request)
    {
        $userId = auth()->id();
        $teamId = (int) $request->input('id', 0);

        if (!$userId || !$teamId) {
            session()->flash('error', 'Yêu cầu không hợp lệ.');
            return redirect('/teams');
        }

        $team = Team::find($teamId);
        if (!$team || (int) $team->owner_id !== (int) $userId) {
            session()->flash('error', 'Bạn không có quyền xóa nhóm này.');
            return redirect('/teams');
        }

        DB::beginTransaction();
        try {
            // 1. Delete tasks belonging to this team
            Task::where('team_id', $teamId)->forceDelete();

            // 2. Delete team members
            TeamMember::where('team_id', $teamId)->delete();

            // 3. Delete the team
            $team->delete();

            DB::commit();
            session()->flash('success', 'Đã xóa nhóm thành công!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('team.delete_failed', ['message' => $e->getMessage()]);
            session()->flash('error', 'Lỗi khi xóa nhóm.');
        }

        return redirect('/teams');
    }

    // =============================================
    // Rời khỏi nhóm (member/admin, owner không được rời)
    // =============================================
    public function leave(Request $request)
    {
        $userId = auth()->id();
        $teamId = (int) $request->input('team_id', 0);

        $membership = TeamMember::where('team_id', $teamId)
            ->where('user_id', $userId)
            ->first();

        if (!$membership) {
            session()->flash('error', 'Bạn không phải thành viên của nhóm này.');
            return redirect('/teams');
        }

        if ($membership->role === 'owner') {
            session()->flash('error', 'Chủ nhóm không thể rời. Hãy chuyển quyền chủ nhóm trước hoặc xóa nhóm.');
            return redirect('/teams/detail?id=' . $teamId);
        }

        $membership->delete();

        // Gỡ các task của user khỏi nhóm (set team_id = null)
        Task::where('team_id', $teamId)
            ->where('user_id', $userId)
            ->update(['team_id' => null]);

        session()->flash('success', 'Bạn đã rời khỏi nhóm.');
        return redirect('/teams');
    }

    // =============================================
    // Xóa thành viên khỏi nhóm (owner/admin kick member)
    // =============================================
    public function removeMember(Request $request)
    {
        $userId = auth()->id();
        $teamId = (int) $request->input('team_id', 0);
        $targetUserId = (int) $request->input('user_id', 0);

        // Kiểm tra quyền của người thực hiện
        $myRole = TeamMember::where('team_id', $teamId)
            ->where('user_id', $userId)
            ->value('role');

        if (!in_array($myRole, ['owner', 'admin'])) {
            session()->flash('error', 'Bạn không có quyền xóa thành viên.');
            return redirect('/teams/detail?id=' . $teamId);
        }

        // Kiểm tra thành viên bị xóa
        $targetMember = TeamMember::where('team_id', $teamId)
            ->where('user_id', $targetUserId)
            ->first();

        if (!$targetMember) {
            session()->flash('error', 'Không tìm thấy thành viên.');
            return redirect('/teams/detail?id=' . $teamId);
        }

        // Owner không thể bị kick
        if ($targetMember->role === 'owner') {
            session()->flash('error', 'Không thể xóa chủ nhóm.');
            return redirect('/teams/detail?id=' . $teamId);
        }

        // Admin chỉ có thể kick member, không kick admin khác
        if ($myRole === 'admin' && $targetMember->role === 'admin') {
            session()->flash('error', 'Admin không thể xóa admin khác. Chỉ chủ nhóm mới có quyền.');
            return redirect('/teams/detail?id=' . $teamId);
        }

        // Không tự kick chính mình (dùng leave thay)
        if ($targetUserId === $userId) {
            session()->flash('error', 'Không thể xóa chính mình. Hãy dùng chức năng "Rời nhóm".');
            return redirect('/teams/detail?id=' . $teamId);
        }

        $targetMember->delete();

        // Gỡ task của member bị kick khỏi nhóm
        Task::where('team_id', $teamId)
            ->where('user_id', $targetUserId)
            ->update(['team_id' => null]);

        session()->flash('success', 'Đã xóa thành viên khỏi nhóm.');
        return redirect('/teams/detail?id=' . $teamId);
    }

    // =============================================
    // Đổi vai trò thành viên (chỉ Owner)
    // =============================================
    public function changeRole(Request $request)
    {
        $userId = auth()->id();
        $teamId = (int) $request->input('team_id', 0);
        $targetUserId = (int) $request->input('user_id', 0);
        $newRole = $request->input('role', 'member');

        // Chỉ owner mới được đổi vai trò
        $myRole = TeamMember::where('team_id', $teamId)
            ->where('user_id', $userId)
            ->value('role');

        if ($myRole !== 'owner') {
            session()->flash('error', 'Chỉ chủ nhóm mới có quyền đổi vai trò.');
            return redirect('/teams/detail?id=' . $teamId);
        }

        // Không đổi vai trò chính mình
        if ($targetUserId === $userId) {
            session()->flash('error', 'Không thể đổi vai trò của chính mình.');
            return redirect('/teams/detail?id=' . $teamId);
        }

        // Chỉ cho phép member/admin
        if (!in_array($newRole, ['member', 'admin'])) {
            session()->flash('error', 'Vai trò không hợp lệ.');
            return redirect('/teams/detail?id=' . $teamId);
        }

        $targetMember = TeamMember::where('team_id', $teamId)
            ->where('user_id', $targetUserId)
            ->first();

        if (!$targetMember || $targetMember->role === 'owner') {
            session()->flash('error', 'Không tìm thấy thành viên hoặc không thể đổi vai trò chủ nhóm.');
            return redirect('/teams/detail?id=' . $teamId);
        }

        $targetMember->update(['role' => $newRole]);

        $roleLabel = $newRole === 'admin' ? 'Admin' : 'Member';
        session()->flash('success', "Đã đổi vai trò thành {$roleLabel}.");
        return redirect('/teams/detail?id=' . $teamId);
    }

    // =============================================
    // Chuyển quyền chủ nhóm (chỉ Owner)
    // =============================================
    public function transferOwnership(Request $request)
    {
        $userId = auth()->id();
        $teamId = (int) $request->input('team_id', 0);
        $targetUserId = (int) $request->input('user_id', 0);

        // Kiểm tra là owner
        $myMembership = TeamMember::where('team_id', $teamId)
            ->where('user_id', $userId)
            ->first();

        if (!$myMembership || $myMembership->role !== 'owner') {
            session()->flash('error', 'Chỉ chủ nhóm mới có quyền chuyển quyền.');
            return redirect('/teams/detail?id=' . $teamId);
        }

        if ($targetUserId === $userId) {
            session()->flash('error', 'Bạn đã là chủ nhóm rồi.');
            return redirect('/teams/detail?id=' . $teamId);
        }

        $targetMember = TeamMember::where('team_id', $teamId)
            ->where('user_id', $targetUserId)
            ->first();

        if (!$targetMember) {
            session()->flash('error', 'Không tìm thấy thành viên.');
            return redirect('/teams/detail?id=' . $teamId);
        }

        DB::beginTransaction();
        try {
            // Người cũ → admin
            $myMembership->update(['role' => 'admin']);

            // Người mới → owner
            $targetMember->update(['role' => 'owner']);

            // Cập nhật owner_id trong bảng teams
            Team::where('id', $teamId)->update(['owner_id' => $targetUserId]);

            DB::commit();
            session()->flash('success', 'Đã chuyển quyền chủ nhóm thành công.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('team.transfer_failed', ['message' => $e->getMessage()]);
            session()->flash('error', 'Chuyển quyền thất bại.');
        }

        return redirect('/teams/detail?id=' . $teamId);
    }

    // =============================================
    // Sửa thông tin nhóm (Owner/Admin)
    // =============================================
    public function edit(Request $request)
    {
        $userId = auth()->id();
        $teamId = (int) $request->query('id', 0);

        $myRole = TeamMember::where('team_id', $teamId)
            ->where('user_id', $userId)
            ->value('role');

        if (!in_array($myRole, ['owner', 'admin'])) {
            session()->flash('error', 'Bạn không có quyền sửa nhóm.');
            return redirect('/teams');
        }

        $team = Team::findOrFail($teamId);

        return view('teams.edit', [
            'team'        => $team,
            'active_page' => 'teams',
        ]);
    }

    public function update(Request $request)
    {
        $userId = auth()->id();
        $teamId = (int) $request->input('id', 0);

        $myRole = TeamMember::where('team_id', $teamId)
            ->where('user_id', $userId)
            ->value('role');

        if (!in_array($myRole, ['owner', 'admin'])) {
            session()->flash('error', 'Bạn không có quyền sửa nhóm.');
            return redirect('/teams');
        }

        $name = trim($request->input('name', ''));
        $description = trim($request->input('description', ''));

        if (empty($name)) {
            return redirect('/teams/edit?id=' . $teamId)
                ->withErrors(['name' => 'Tên nhóm không được để trống.'])
                ->withInput();
        }

        if (mb_strlen($name) > 100) {
            return redirect('/teams/edit?id=' . $teamId)
                ->withErrors(['name' => 'Tên nhóm tối đa 100 ký tự.'])
                ->withInput();
        }

        Team::where('id', $teamId)->update([
            'name'        => $name,
            'description' => $description,
        ]);

        session()->flash('success', 'Đã cập nhật thông tin nhóm.');
        return redirect('/teams/detail?id=' . $teamId);
    }
}
