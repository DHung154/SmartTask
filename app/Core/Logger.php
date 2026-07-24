<?php

namespace App\Core;

final class Logger
{
    public static function info(string $event, array $context = []): void
    {
        self::write('info', $event, $context);
    }

    public static function error(string $event, array $context = []): void
    {
        self::write('error', $event, $context);
    }

    private static function write(string $level, string $event, array $context): void
    {
        unset($context['password'], $context['token'], $context['api_token'], $context['MAIL_PASS']);
        $dir = dirname(__DIR__, 2) . '/storage/logs';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $record = [
            'time' => gmdate('c'),
            'level' => $level,
            'event' => $event,
            'context' => $context,
        ];
        file_put_contents(
            $dir . '/app-' . date('Y-m-d') . '.log',
            json_encode($record) . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );
    }
}
