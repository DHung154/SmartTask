<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\ResetPassword;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_page_is_reachable(): void
    {
        $this->get('/forgot-password')->assertOk()->assertSee('Quên mật khẩu');
    }

    public function test_login_page_links_to_forgot_password(): void
    {
        $this->get('/login')->assertOk()->assertSee('/forgot-password', false);
    }

    public function test_reset_link_is_sent_to_registered_user(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email])
            ->assertRedirect('/login');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_unknown_email_gets_same_message_and_sends_nothing(): void
    {
        Notification::fake();

        $this->post('/forgot-password', ['email' => 'khong-ton-tai@example.com'])
            ->assertRedirect('/login')
            ->assertSessionHas('success');

        Notification::assertNothingSent();
    }

    public function test_invalid_email_format_is_rejected(): void
    {
        $this->post('/forgot-password', ['email' => 'khong-phai-email'])
            ->assertSessionHasErrors('email');
    }

    public function test_user_can_reset_password_with_valid_token(): void
    {
        $user = User::factory()->create();

        $token = null;
        Notification::fake();
        $this->post('/forgot-password', ['email' => $user->email]);
        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use (&$token) {
            $token = $notification->token;
            return true;
        });

        $this->assertNotNull($token);

        $this->post('/reset-password', [
            'token'                 => $token,
            'email'                 => $user->email,
            'password'              => 'matkhaumoi123',
            'password_confirmation' => 'matkhaumoi123',
        ])->assertRedirect('/login');

        $this->assertTrue(Hash::check('matkhaumoi123', $user->fresh()->password));
    }

    public function test_reset_fails_with_wrong_token(): void
    {
        $user = User::factory()->create();
        $originalHash = $user->password;

        $this->post('/reset-password', [
            'token'                 => 'token-bia-dat',
            'email'                 => $user->email,
            'password'              => 'matkhaumoi123',
            'password_confirmation' => 'matkhaumoi123',
        ])->assertSessionHasErrors('email');

        $this->assertSame($originalHash, $user->fresh()->password);
    }

    public function test_short_password_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->post('/reset-password', [
            'token'                 => 'bat-ky',
            'email'                 => $user->email,
            'password'              => '123',
            'password_confirmation' => '123',
        ])->assertSessionHasErrors('password');
    }

    public function test_reset_page_renders_with_token(): void
    {
        $this->get('/reset-password/abc123?email=' . urlencode('a@b.com'))
            ->assertOk()
            ->assertSee('abc123', false)
            ->assertSee('a@b.com', false);
    }

    public function test_reset_token_row_is_created(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        $this->assertDatabaseHas('password_reset_tokens', ['email' => $user->email]);
        $this->assertNotEmpty(DB::table('password_reset_tokens')->where('email', $user->email)->value('token'));
    }
}
