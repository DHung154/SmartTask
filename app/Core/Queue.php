<?php

namespace App\Core;

use PDO;

final class Queue
{
    public static function push(string $type, array $payload): int
    {
        $statement = self::db()->prepare(
            'INSERT INTO job_queue (type, payload, available_at) VALUES (:type, :payload, NOW())'
        );
        $statement->execute([':type' => $type, ':payload' => json_encode($payload, JSON_UNESCAPED_UNICODE)]);
        return (int) self::db()->lastInsertId();
    }

    public static function reserve(string $type): ?array
    {
        $db = self::db();
        $db->beginTransaction();
        try {
            $statement = $db->prepare(
                "SELECT * FROM job_queue WHERE type = :type AND status = 'pending' "
                . 'AND available_at <= NOW() ORDER BY id LIMIT 1 FOR UPDATE'
            );
            $statement->execute([':type' => $type]);
            $job = $statement->fetch();
            if (!$job) {
                $db->commit();
                return null;
            }
            $db->prepare(
                "UPDATE job_queue SET status = 'processing', attempts = attempts + 1, "
                . 'reserved_at = NOW() WHERE id = :id'
            )
                ->execute([':id' => $job['id']]);
            $db->commit();
            $job['payload'] = json_decode($job['payload'], true) ?: [];
            return $job;
        } catch (\Throwable $exception) {
            $db->rollBack();
            throw $exception;
        }
    }

    public static function complete(int $id): void
    {
        self::db()->prepare("UPDATE job_queue SET status = 'completed', completed_at = NOW() WHERE id = :id")
            ->execute([':id' => $id]);
    }

    public static function fail(int $id, int $attempts, string $message): void
    {
        $status = $attempts >= 3 ? 'failed' : 'pending';
        self::db()->prepare(
            'UPDATE job_queue SET status = :status, available_at = DATE_ADD(NOW(), INTERVAL 5 MINUTE), '
            . 'error_message = :error WHERE id = :id'
        )
            ->execute([':status' => $status, ':error' => substr($message, 0, 1000), ':id' => $id]);
    }

    private static function db(): PDO
    {
        static $db;
        if ($db instanceof PDO) {
            return $db;
        }
        $config = require __DIR__ . '/../../config/database.php';
        return $db = new PDO(
            "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset=utf8mb4",
            $config['username'],
            $config['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
    }
}
