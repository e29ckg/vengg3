<?php
declare(strict_types=1);

final class LoginRateLimiter
{
    public function __construct(private string $directory) {}
    private function mutate(string $ip, string $username, bool $success): bool
    {
        if (!is_dir($this->directory) && !@mkdir($this->directory, 0750, true) && !is_dir($this->directory)) throw new RuntimeException('Login limiter storage unavailable');
        // Share the account counter across IPs and always lock account before IP.
        $paths = [
            'account' => $this->directory . '/account-' . hash('sha256', strtolower($username)) . '.json',
            'ip' => $this->directory . '/ip-' . hash('sha256', $ip) . '.json',
        ];
        $handles = [];
        try {
            foreach ($paths as $bucket => $path) {
                $handle = @fopen($path, 'c+');
                if (!$handle) throw new RuntimeException('Login limiter storage unavailable');
                $handles[$bucket] = $handle;
                if (!flock($handle, LOCK_EX)) throw new RuntimeException('Login limiter lock unavailable');
            }
            $now = time();
            $state = [];
            foreach (['account' => 900, 'ip' => 300] as $bucket => $window) {
                rewind($handles[$bucket]);
                $stored = json_decode(stream_get_contents($handles[$bucket]), true);
                $state[$bucket] = is_array($stored) && ($stored['until'] ?? 0) > $now
                    ? $stored : ['count' => 0, 'until' => $now + $window];
            }
            if (!$success && ($state['account']['count'] >= 10 || $state['ip']['count'] >= 60)) return false;
            foreach ($state as $bucket => $entry) {
                $entry['count'] = $success ? max(0, $entry['count'] - 1) : $entry['count'] + 1;
                if ($success && $bucket === 'account') $entry['count'] = 0;
                $handle = $handles[$bucket];
                rewind($handle);
                if (!ftruncate($handle, 0) || fwrite($handle, json_encode($entry, JSON_THROW_ON_ERROR)) === false || !fflush($handle)) {
                    throw new RuntimeException('Login limiter write failed');
                }
            }
            return true;
        } finally {
            foreach (array_reverse($handles) as $handle) { flock($handle, LOCK_UN); fclose($handle); }
        }
    }
    public function attempt(string $ip, string $username): bool { return $this->mutate($ip, $username, false); }
    public function succeeded(string $ip, string $username): void { $this->mutate($ip, $username, true); }
}
