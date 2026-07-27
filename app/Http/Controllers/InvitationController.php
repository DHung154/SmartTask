<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\ActivityLog;
use App\Models\TeamInvitation;
use App\Models\TeamMember;

class InvitationController extends Controller
{
    /**
     * Chấp nhận lời mời: tạo bản ghi thành viên rồi đóng lời mời.
     */
    public function accept(Request $request)
    {
        $userId = auth()->id();
        $invitation = $this->findPending($request, $userId);

        if (!$invitation) {
            session()->flash('error', 'Lời mời không tồn tại hoặc đã được xử lý.');
            return redirect($this->backTarget($request));
        }

        $teamName = $invitation->team->name ?? 'nhóm';

        DB::beginTransaction();
        try {
            $alreadyMember = TeamMember::where('team_id', $invitation->team_id)
                ->where('user_id', $userId)
                ->exists();

            if (!$alreadyMember) {
                TeamMember::create([
                    'team_id' => $invitation->team_id,
                    'user_id' => $userId,
                    'role'    => $invitation->role,
                ]);
            }

            $invitation->update([
                'status'       => 'accepted',
                'responded_at' => now(),
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('invitation.accept_failed', ['id' => $invitation->id, 'message' => $e->getMessage()]);
            session()->flash('error', 'Không tham gia được nhóm. Hãy thử lại.');
            return redirect($this->backTarget($request));
        }

        ActivityLog::log($userId, 'accept', 'team', $invitation->team_id, 'Tham gia nhóm: ' . $teamName);

        session()->flash('success', 'Bạn đã tham gia nhóm "' . $teamName . '".');
        return redirect('/teams/detail?id=' . (int) $invitation->team_id);
    }

    /**
     * Từ chối lời mời: giữ lại bản ghi để chủ nhóm biết kết quả.
     */
    public function decline(Request $request)
    {
        $userId = auth()->id();
        $invitation = $this->findPending($request, $userId);

        if (!$invitation) {
            session()->flash('error', 'Lời mời không tồn tại hoặc đã được xử lý.');
            return redirect($this->backTarget($request));
        }

        $teamName = $invitation->team->name ?? 'nhóm';

        $invitation->update([
            'status'       => 'declined',
            'responded_at' => now(),
        ]);

        ActivityLog::log($userId, 'decline', 'team', $invitation->team_id, 'Từ chối lời mời nhóm: ' . $teamName);

        session()->flash('success', 'Đã từ chối lời mời vào nhóm "' . $teamName . '".');
        return redirect($this->backTarget($request));
    }

    private function findPending(Request $request, $userId): ?TeamInvitation
    {
        $id = (int) $request->input('id', 0);
        if (!$id) return null;

        return TeamInvitation::with('team:id,name')
            ->where('id', $id)
            ->where('user_id', $userId)
            ->where('status', 'pending')
            ->first();
    }

    private function backTarget(Request $request, string $default = '/'): string
    {
        $target = $request->input('redirect', '');
        return is_string($target) && preg_match('#^/(?![\\\\\/])#', $target) ? $target : $default;
    }
}
