<?php
declare(strict_types=1);

// Run once per minute from Windows Task Scheduler. This entry point is CLI only.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/Services/TelegramService.php';
require_once __DIR__ . '/../src/Services/DailyTelegramNotifier.php';

try {
    $args = array_slice($argv, 1);
    $dryRun = in_array('--dry-run', $args, true);
    $preview = in_array('--preview', $args, true);
    $sendTest = in_array('--send-test', $args, true);
    $atValue = null;
    foreach ($args as $arg) {
        if (str_starts_with($arg, '--at=')) {
            $atValue = substr($arg, 5);
        } elseif (!in_array($arg, ['--dry-run', '--preview', '--send-test'], true)) {
            throw new InvalidArgumentException('Unknown option');
        }
    }
    if (($atValue !== null || $preview) && !$dryRun) {
        throw new InvalidArgumentException('--at and --preview require --dry-run');
    }
    if ($sendTest && ($dryRun || $preview || $atValue !== null)) {
        throw new InvalidArgumentException('--send-test cannot be combined with other options');
    }

    $zone = new DateTimeZone('Asia/Bangkok');
    $at = new DateTimeImmutable('now', $zone);
    if ($atValue !== null) {
        $at = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $atValue, $zone);
        if (!$at || $at->format('Y-m-d\TH:i') !== $atValue) {
            throw new InvalidArgumentException('Use --at=YYYY-MM-DDTHH:MM');
        }
    }

    $connection = (new Database())->getConnection();
    $notifier = new DailyTelegramNotifier($connection, new TelegramService($connection));
    if ($sendTest) {
        if (!$notifier->sendTest($at)) {
            throw new RuntimeException('Telegram test send failed');
        }
        echo "Telegram test message sent\n";
        exit(0);
    }

    $result = $notifier->run($at, $dryRun, $preview);
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit($result['failed'] > 0 ? 1 : 0);
} catch (Throwable $error) {
    error_log('Daily Telegram notification failed: ' . get_class($error));
    fwrite(STDERR, 'Daily Telegram notification failed: ' . get_class($error) . PHP_EOL);
    exit(1);
}
