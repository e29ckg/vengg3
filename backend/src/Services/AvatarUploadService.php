<?php
declare(strict_types=1);

final class AvatarUploadService
{
    public static function validate(string $path, string $name): string
    {
        if (!is_file($path) || filesize($path) > 2097152 || filesize($path) === 0) throw new RuntimeException('ขนาดรูปไม่ถูกต้อง');
        $types = ['image/jpeg' => ['jpg','jpeg'], 'image/png' => ['png'], 'image/gif' => ['gif'], 'image/webp' => ['webp']];
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($path);
        $dimensions = @getimagesize($path);
        if (!isset($types[$mime]) || !in_array($extension, $types[$mime], true) || !$dimensions ||
            $dimensions[0] < 1 || $dimensions[1] < 1 || $dimensions[0] > 4096 || $dimensions[1] > 4096 || $dimensions[0] * $dimensions[1] > 16000000) {
            throw new RuntimeException('ใช้รูป jpg, png, gif หรือ webp จริง ขนาดไม่เกิน 2 MB และด้านละไม่เกิน 4096 pixels');
        }
        return $types[$mime][0];
    }
    public static function safeStoredName($name): bool
    {
        return is_string($name) && preg_match('/\A(?:user_|avatar_)[A-Za-z0-9_-]+\.(?:jpg|jpeg|png|gif|webp)\z/', $name) === 1;
    }
}
