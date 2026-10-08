<?php
declare(strict_types=1);

final class SessionSecurity
{
    public const TTL = 28800;
    public static function issue(?int $now = null): string
    {
        return 'v1.' . ($now ?? time()) . '.' . bin2hex(random_bytes(32));
    }
    public static function fingerprint(string $token): string
    {
        // 128-bit SHA-256 fingerprint fits existing auth_key varchar(32).
        return substr(hash('sha256', $token), 0, 32);
    }
    public static function valid(string $token, ?int $now = null): bool
    {
        if (!preg_match('/\Av1\.([0-9]{10})\.[a-f0-9]{64}\z/', $token, $matches)) return false;
        $issued = (int)$matches[1];
        $now ??= time();
        return $issued <= $now + 30 && $issued + self::TTL > $now;
    }
    public static function passwordAllowed($password): bool
    {
        return is_string($password) && strlen($password) >= 12 && strlen($password) <= 72;
    }
}
