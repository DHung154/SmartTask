<?php
namespace App\Core;

/**
 * Env Class
 *
 * �?c c?u h�nh t? file .env ? thu m?c g?c d? �n.
 *
 * V� sao c?n .env?
 * - Kh�ng hard-code m?t kh?u database v�o source code
 * - M?i m�y (m�y b?n, m�y b?n c�ng nh�m, server) c� c?u h�nh ri�ng
 * - File .env du?c .gitignore n�n kh�ng b? d?y l�n GitHub
 *
 * C� ph�p h? tr?:
 *   DB_HOST=localhost
 *   DB_PASS="m?t kh?u c� d?u c�ch"
 *   # D�ng b?t d?u b?ng # l� ghi ch�
 */
class Env
{
    /** �� n?p file .env chua (ch? n?p 1 l?n cho m?i request) */
    private static $loaded = false;

    /** C�c bi?n d� d?c du?c t? file .env */
    private static $vars = [];

    /**
     * N?p file .env v�o b? nh?
     *
     * N?u file kh�ng t?n t?i th� b? qua (d�ng gi� tr? m?c d?nh),
     * d? d? �n v?n ch?y du?c ngay sau khi clone m� chua t?o .env.
     *
     * @param string|null $path �u?ng d?n t?i file .env
     */
    public static function load($path = null)
    {
        if (self::$loaded) {
            return;
        }
        self::$loaded = true;

        $path = $path ?? __DIR__ . '/../../.env';

        if (!is_file($path) || !is_readable($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);

            // B? qua d�ng tr?ng v� d�ng ghi ch�
            if ($line === '' || $line[0] === '#') {
                continue;
            }

            // B? qua d�ng kh�ng c� d?u =
            if (strpos($line, '=') === false) {
                continue;
            }

            $parts = explode('=', $line, 2);
            $key   = trim($parts[0]);
            $value = trim($parts[1]);

            if ($key === '') {
                continue;
            }

            // G? d?u nh�y bao quanh gi� tr?: DB_PASS="abc" -> abc
            $len = strlen($value);
            if ($len >= 2) {
                $first = $value[0];
                $last  = $value[$len - 1];
                if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                    $value = substr($value, 1, -1);
                }
            }

            self::$vars[$key] = $value;
        }
    }

    /**
     * L?y m?t bi?n c?u h�nh
     *
     * Th? t? uu ti�n: file .env -> bi?n m�i tru?ng h? th?ng -> gi� tr? m?c d?nh
     *
     * @param string $key     T�n bi?n, v� d? 'DB_HOST'
     * @param mixed  $default Gi� tr? d�ng khi kh�ng t�m th?y
     * @return mixed
     */
    public static function get($key, $default = null)
    {
        self::load();

        if (array_key_exists($key, self::$vars)) {
            return self::$vars[$key];
        }

        $fromSystem = getenv($key);

        return $fromSystem === false ? $default : $fromSystem;
    }

    /**
     * L?y bi?n c?u h�nh d?ng boolean
     *
     * Coi "true", "1", "yes", "on" l� true (kh�ng ph�n bi?t hoa thu?ng).
     *
     * @param string $key
     * @param bool   $default
     * @return bool
     */
    public static function bool($key, $default = false)
    {
        $value = self::get($key, null);

        if ($value === null) {
            return $default;
        }

        return in_array(strtolower((string)$value), ['true', '1', 'yes', 'on'], true);
    }
}
