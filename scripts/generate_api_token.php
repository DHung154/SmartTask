<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Eloquent;
use App\Entities\UserRecord;

$email = $argv[1] ?? '';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Usage: php scripts/generate_api_token.php user@example.com\n");
    exit(1);
}

Eloquent::boot();
$user = UserRecord::query()->where('email', $email)->first();
if ($user === null) {
    fwrite(STDERR, "User not found.\n");
    exit(1);
}

$token = bin2hex(random_bytes(32));
$user->api_token = hash('sha256', $token);
$user->save();
echo "API token (save it now; it is shown once): {$token}\n";
