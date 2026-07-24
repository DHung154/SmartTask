<?php
namespace App\Core;

/**
 * CSRF Protection
 *
 * CSRF (Cross-Site Request Forgery) l� ki?u t?n c�ng m� trang web d?c h?i
 * l?a tr�nh duy?t c?a b?n g?i request t?i ?ng d?ng n�y khi b?n dang dang nh?p.
 *
 * V� d? t?n c�ng khi CHUA c� b?o v?:
 *   Trang x?u ch�n <img src="http://localhost:8000/tasks/delete?id=5">
 *   Tr�nh duy?t t? g?i k�m cookie session -> task b? x�a m� b?n kh�ng h? b?m g�.
 *
 * C�ch ch?ng:
 *   1. Sinh m?t chu?i ng?u nhi�n (token) luu trong session
 *   2. Nh�ng token d� v�o m?i form POST
 *   3. Khi nh?n POST, so s�nh token g?i l�n v?i token trong session
 *   Trang web kh�c kh�ng d?c du?c session n�n kh�ng do�n du?c token.
 */
class Csrf
{
    /** T�n field ?n trong form v� key luu trong session */
    const FIELD = '_token';
    const SESSION_KEY = '_csrf_token';

    /**
     * L?y token hi?n t?i, t? sinh m?i n?u chua c�
     *
     * Token d�ng chung cho c? phi�n dang nh?p (per-session token).
     *
     * @return string Chu?i hex 64 k� t?
     */
    public static function token()
    {
        $token = Session::get(self::SESSION_KEY);

        if (!is_string($token) || $token === '') {
            // random_bytes() l� ngu?n ng?u nhi�n an to�n v? m?t m� h?c
            $token = bin2hex(random_bytes(32));
            Session::set(self::SESSION_KEY, $token);
        }

        return $token;
    }

    /**
     * Sinh s?n th? input ?n d? nh�ng v�o form
     *
     * C�ch d�ng trong view:  <?= \App\Core\Csrf::field() ?>
     *
     * @return string HTML c?a input hidden
     */
    public static function field()
    {
        return '<input type="hidden" name="' . self::FIELD . '" value="'
            . htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Ki?m tra token g?i l�n c� h?p l? kh�ng
     *
     * D�ng hash_equals() thay v� === d? ch?ng timing attack
     * (so s�nh chu?i trong th?i gian kh�ng d?i).
     *
     * @return bool True n?u token h?p l?
     */
    public static function check()
    {
        $sent   = $_POST[self::FIELD] ?? '';
        $stored = Session::get(self::SESSION_KEY);

        if (!is_string($sent) || !is_string($stored) || $stored === '') {
            return false;
        }

        return hash_equals($stored, $sent);
    }
}
