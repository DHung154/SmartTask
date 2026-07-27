<?php

namespace App\Providers;

use App\Services\NotificationCenter;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Chuông thông báo nằm trên layout chung nên nạp dữ liệu qua view composer,
        // khỏi phải truyền từ từng controller.
        View::composer('layouts.app', function ($view) {
            $view->with('notifications', auth()->check()
                ? NotificationCenter::forUser(auth()->id())
                : NotificationCenter::empty());
        });

        // Email đặt lại mật khẩu bằng tiếng Việt, dùng view riêng của dự án.
        ResetPassword::toMailUsing(function ($notifiable, string $token) {
            $url = url('/reset-password/' . $token . '?email=' . urlencode($notifiable->getEmailForPasswordReset()));

            return (new MailMessage)
                ->subject('Đặt lại mật khẩu SmartTask')
                ->view('emails.reset-password', [
                    'user'    => $notifiable,
                    'url'     => $url,
                    'minutes' => config('auth.passwords.users.expire', 60),
                ]);
        });
    }
}
