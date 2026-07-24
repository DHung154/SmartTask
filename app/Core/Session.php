<?php
namespace App\Core;

/**
 * Session Management Class
 * 
 * Handles all session operations (start, set, get, destroy)
 * 
 * Why a Session class?
 * - Cleaner code: Session::get('user_id') is more readable than $_SESSION['user_id']
 * - Safety: Automatically starts session and prevents errors
 * - Centralized: All session logic in one place
 */
class Session
{
    /**
     * Start the session if not already started
     */
    public static function start()
    {
        if (session_status() === PHP_SESSION_NONE) {
            $sessionPath = __DIR__ . '/../../storage/sessions';
            if (!is_dir($sessionPath)) {
                mkdir($sessionPath, 0777, true);
            }

            session_save_path($sessionPath);
            session_start();
        }
    }

    /**
     * Set a session variable
     * 
     * @param string $key Session key
     * @param mixed $value Value to store
     */
    public static function set($key, $value)
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    /**
     * Get a session variable
     * 
     * @param string $key Session key
     * @param mixed $default Default value if key doesn't exist
     * @return mixed
     */
    public static function get($key, $default = null)
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Check if a session variable exists
     * 
     * @param string $key Session key
     * @return bool
     */
    public static function has($key)
    {
        self::start();
        return isset($_SESSION[$key]);
    }

    /**
     * Remove a session variable
     * 
     * @param string $key Session key
     */
    public static function remove($key)
    {
        self::start();
        unset($_SESSION[$key]);
    }

    /**
     * Destroy the entire session
     * Used for logout
     */
    public static function destroy()
    {
        self::start();
        session_destroy();
        $_SESSION = [];
    }

    /**
     * Set a flash message (one-time message)
     * 
     * @param string $key Flash message key
     * @param string $message Message content
     */
    public static function flash($key, $message)
    {
        self::set('flash_' . $key, $message);
    }

    /**
     * Get and remove a flash message
     *
     * @param string $key Flash message key
     * @return string|null
     */
    public static function getFlash($key)
    {
        $message = self::get('flash_' . $key);
        self::remove('flash_' . $key);
        return $message;
    }

    /**
     * Luu t?m d? li?u ngu?i d�ng v?a nh?p v�o form
     *
     * V� sao c?n?
     * Khi form nh?p sai (VD: thi?u ti�u d?), controller redirect v? l?i form.
     * N?u kh�ng luu, m?i th? ngu?i d�ng d� g� s? m?t s?ch -> tr?i nghi?m r?t t?.
     * Luu l?i r?i d? ngu?c v�o form gi�p h? ch? ph?i s?a ch? sai.
     *
     * @param array $data D? li?u t? $_POST (d� b? password v� token)
     */
    public static function setOld(array $data)
    {
        // Kh�ng bao gi? luu l?i m?t kh?u hay CSRF token
        unset($data['password'], $data['confirm_password'], $data['current_password'],
              $data['new_password'], $data['_token']);

        self::set('_old_input', $data);
    }

    /**
     * L?y d? li?u form cu v� x�a lu�n (ch? d�ng du?c 1 l?n)
     *
     * @return array
     */
    public static function pullOld()
    {
        $old = self::get('_old_input', []);
        self::remove('_old_input');

        return is_array($old) ? $old : [];
    }

    /**
     * Luu danh s�ch l?i theo t?ng field
     *
     * VD: ['email' => 'Email kh�ng h?p l?', 'password' => 'M?t kh?u qu� ng?n']
     *
     * @param array $errors
     */
    public static function setErrors(array $errors)
    {
        self::set('_form_errors', $errors);
    }

    /**
     * L?y danh s�ch l?i v� x�a lu�n (ch? d�ng du?c 1 l?n)
     *
     * @return array
     */
    public static function pullErrors()
    {
        $errors = self::get('_form_errors', []);
        self::remove('_form_errors');

        return is_array($errors) ? $errors : [];
    }
}
