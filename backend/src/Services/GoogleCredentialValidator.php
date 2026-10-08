<?php
declare(strict_types=1);

final class GoogleCredentialValidator
{
    public static function validate(string $content): array
    {
        if (strlen($content) > 65536) throw new RuntimeException('ไฟล์ credentials.json ต้องไม่เกิน 64 KB');
        $data = json_decode($content, true);
        if (!is_array($data) || ($data['type'] ?? '') !== 'service_account' ||
            !is_string($data['client_email'] ?? null) || !filter_var($data['client_email'], FILTER_VALIDATE_EMAIL) || !str_ends_with($data['client_email'], '.gserviceaccount.com') ||
            !is_string($data['private_key'] ?? null) || ($data['token_uri'] ?? '') !== 'https://oauth2.googleapis.com/token' ||
            !in_array($data['universe_domain'] ?? 'googleapis.com', ['googleapis.com'], true)) {
            throw new RuntimeException('ใช้ไฟล์ service-account credentials จาก Google ที่ถูกต้อง');
        }
        $key = @openssl_pkey_get_private($data['private_key']);
        $details = $key ? openssl_pkey_get_details($key) : [];
        if (!$key || ($details['bits'] ?? 0) < 2048 || ($details['type'] ?? null) !== OPENSSL_KEYTYPE_RSA) throw new RuntimeException('private key ในไฟล์ไม่ถูกต้อง');
        $allowed = ['type','project_id','private_key_id','private_key','client_email','client_id','token_uri','universe_domain'];
        return array_intersect_key($data, array_flip($allowed)) + ['auth_uri' => 'https://accounts.google.com/o/oauth2/auth'];
    }
}
