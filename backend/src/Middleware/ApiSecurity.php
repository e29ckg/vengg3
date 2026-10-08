<?php
declare(strict_types=1);

final class ApiSecurity
{
    public static function bootstrap(): void
    {
        ini_set('display_errors', '0');
        ini_set('log_errors', '1');
        header('Content-Type: application/json; charset=UTF-8');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store');
        header('Referrer-Policy: no-referrer');
        if (($_SERVER['HTTPS'] ?? '') === 'on') header('Strict-Transport-Security: max-age=31536000');
        set_exception_handler(static function (Throwable $error): void {
            error_log('API failure: ' . get_class($error));
            http_response_code(500);
            echo json_encode(['error' => 'เกิดข้อผิดพลาดภายในระบบ กรุณาติดต่อผู้ดูแล']);
        });
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        $allowed = array_filter(array_map('trim', explode(',', getenv('CORS_ALLOWED_ORIGINS') ?: 'http://localhost:5173,http://127.0.0.1:5173')));
        if (in_array($origin, $allowed, true)) {
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Vary: Origin');
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        }
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(in_array($origin, $allowed, true) ? 204 : 403);
            exit;
        }
        if (in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT'], true) && !str_starts_with(strtolower($_SERVER['CONTENT_TYPE'] ?? ''), 'multipart/form-data')) {
            if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 1048576) { http_response_code(413); echo json_encode(['error' => 'ข้อมูลมีขนาดใหญ่เกินไป']); exit; }
            $handle = fopen('php://input', 'r');
            $raw = stream_get_contents($handle, 1048577);
            fclose($handle);
            if (strlen($raw) > 1048576) { http_response_code(413); echo json_encode(['error' => 'ข้อมูลมีขนาดใหญ่เกินไป']); exit; }
            $body = json_decode($raw, true);
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($body)) { http_response_code(400); echo json_encode(['error' => 'รูปแบบ JSON ไม่ถูกต้อง']); exit; }
        }
    }

    public static function enforceMethod(string $route): void
    {
        $read = ['test','auth/me','user/profile','admin/user/list','admin/options/get','admin/ven_time','admin/ven_com/list','user/ven_change_history','ven/list','ven/detail','ven/eligible_users/get_by_sub','admin/ven_user/allUsers','admin/ven_user/get_by_sub','admin/ven_schedule/list_month','admin/ven_schedule/user_list_by_sub','admin/ven_schedule/ven_name_list','report/monthly','report/schedule-details','users/list','report/personal-schedule','admin/ven_approve/list','finance/report','get_commands','system_settings','admin/backup/images','admin/backup/sql','admin/google_settings/get','admin/telegram_settings','admin/logs/get','public/latest_update','admin/templates/list','documents/template','admin/program/status'];
        $write = ['auth/login','auth/logout','user/profile/update','user/profile/password','user/profile/upload_avatar','admin/user/create','admin/user/status','admin/user/update','admin/user/delete','admin/users/update_order','admin/options/add','admin/options/delete','admin/ven_com/create','admin/ven_com/update','admin/ven_com/delete','admin/ven_com/toggle_status','admin/ven_com/update_status','ven/transfer/perform','ven/transfer/cancel','admin/ven/setting','admin/ven_user/add','admin/ven_user/remove','admin/ven_user/update_order','admin/ven_schedule/add','admin/ven_schedule/remove','admin/ven_schedule/sync_google','admin/ven_approve/force_update','admin/system_settings','admin/agency_settings','admin/settings/update_toggle','admin/google_settings/update','admin/google_settings/upload','admin/telegram_settings/update','admin/telegram_settings/test','admin/telegram_settings/manual_notify','admin/templates/upload','admin/templates/validate','admin/templates/reset','admin/program/update'];
        if (!in_array($route, array_merge($read, $write), true)) return;
        $methods = in_array($route, $read, true) ? ['GET'] : ['POST'];
        if (in_array($route, ['admin/system_settings','admin/agency_settings'], true)) $methods = ['GET','POST'];
        if ($route === 'user/profile/update') $methods = ['POST','PUT'];
        if (in_array($route, ['admin/ven_com/delete','admin/templates/reset'], true)) $methods = ['DELETE'];
        if ($route === 'admin/options/delete') $methods = ['POST','DELETE'];
        if ($route === 'admin/ven/setting') {
            $methods = in_array($_GET['action'] ?? '', ['ven_full','ven_name_list','list_venname','get_by_id'], true) ? ['GET'] : ['POST'];
        }
        if (!in_array($_SERVER['REQUEST_METHOD'], $methods, true)) {
            header('Allow: ' . implode(', ', $methods));
            http_response_code(405); echo json_encode(['error' => 'Method not allowed']); exit;
        }
    }
}
