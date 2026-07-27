<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invited_by')->constrained('users')->cascadeOnDelete();
            $table->string('role', 20)->default('member');
            $table->enum('status', ['pending', 'accepted', 'declined'])->default('pending');
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->unique(['team_id', 'user_id'], 'uq_invitation_team_user');
            $table->index(['user_id', 'status'], 'idx_invitation_user_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_invitations');
    }
};
