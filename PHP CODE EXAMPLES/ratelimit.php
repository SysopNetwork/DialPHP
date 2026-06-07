<?php
/**
 * DialPHP — IP-based Login Rate Limiter
 *
 * Tracks failed login attempts per IP address in a local JSON file.
 * After RL_MAX_ATTEMPTS failures within RL_WINDOW seconds, the IP
 * is locked out for RL_LOCKOUT seconds.
 *
 * No database or external service required.
 *
 * Usage:
 *   require_once __DIR__ . '/ratelimit.php';
 *   $ip  = rl_ip();
 *   $rl  = rl_check($ip);          // ['allowed' => bool, 'wait' => int seconds]
 *   if (!$rl['allowed']) { ... show lockout message ... }
 *   // On any auth failure:
 *   rl_fail($ip);
 *   // On successful login:
 *   rl_clear($ip);
 *
 * Copyright (c) 2026 Mark Laudenbach
 * Sysop Network
 * Licensed under the MIT License
 */

// ----------------------------------------------------------------
// Configuration
// ----------------------------------------------------------------

/** Path to the attempts file. Must be writable by the web server user. */
define('RL_FILE',         __DIR__ . '/data/login_attempts.json');

/** Number of consecutive failures before lockout. */
define('RL_MAX_ATTEMPTS', 5);

/** Sliding window in seconds during which attempts are counted. */
define('RL_WINDOW',       900);    // 15 minutes

/** How long a locked-out IP must wait before trying again. */
define('RL_LOCKOUT',      900);    // 15 minutes

// Create the data directory if it does not exist
if (!is_dir(dirname(RL_FILE))) {
    @mkdir(dirname(RL_FILE), 0750, true);
}


// ----------------------------------------------------------------
// Public functions
// ----------------------------------------------------------------

/**
 * Return the client's IP address.
 *
 * Uses REMOTE_ADDR (the TCP peer), which is reliable on a direct VPS.
 * If your server sits behind a trusted reverse proxy, you may substitute
 * X-Forwarded-For, but only after validating the proxy chain — that header
 * can be spoofed by clients to bypass rate limiting.
 */
function rl_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * Check whether the IP is currently allowed to attempt a login.
 *
 * @return array{allowed: bool, wait: int}
 *   'allowed' — false if the IP is locked out
 *   'wait'    — seconds remaining in the lockout (0 when allowed)
 */
function rl_check(string $ip): array
{
    $now  = time();
    $data = _rl_load();
    $entry = $data[$ip] ?? null;

    if ($entry === null) {
        return ['allowed' => true, 'wait' => 0];
    }

    // Active lockout
    if (!empty($entry['locked_until']) && $now < (int)$entry['locked_until']) {
        return ['allowed' => false, 'wait' => (int)$entry['locked_until'] - $now];
    }

    // Tracking window expired — treat this IP as clean
    if ($now - (int)($entry['first_at'] ?? 0) >= RL_WINDOW) {
        return ['allowed' => true, 'wait' => 0];
    }

    // Within window but not yet locked out
    return ['allowed' => true, 'wait' => 0];
}

/**
 * Record one failed login attempt for the IP.
 * Triggers a lockout if RL_MAX_ATTEMPTS is reached.
 * Uses an exclusive file lock for the entire read-modify-write cycle
 * to prevent concurrent requests from bypassing the threshold.
 */
function rl_fail(string $ip): void
{
    $now = time();
    $fh  = @fopen(RL_FILE, 'c+');
    if ($fh === false) { return; }

    flock($fh, LOCK_EX);

    $raw  = stream_get_contents($fh);
    $data = (is_string($raw) && $raw !== '') ? json_decode($raw, true) : [];
    if (!is_array($data)) { $data = []; }

    $entry = $data[$ip] ?? null;

    if ($entry === null || ($now - (int)($entry['first_at'] ?? 0) >= RL_WINDOW)) {
        $data[$ip] = ['count' => 1, 'first_at' => $now, 'locked_until' => null];
    } else {
        $data[$ip]['count']++;
        if ((int)$data[$ip]['count'] >= RL_MAX_ATTEMPTS) {
            $data[$ip]['locked_until'] = $now + RL_LOCKOUT;
        }
    }

    $json = json_encode(_rl_cleanup($data));
    ftruncate($fh, 0);
    rewind($fh);
    fwrite($fh, $json);
    flock($fh, LOCK_UN);
    fclose($fh);
}

/**
 * Clear the attempt record for an IP after a successful login.
 */
function rl_clear(string $ip): void
{
    $fh = @fopen(RL_FILE, 'c+');
    if ($fh === false) { return; }

    flock($fh, LOCK_EX);

    $raw  = stream_get_contents($fh);
    $data = (is_string($raw) && $raw !== '') ? json_decode($raw, true) : [];
    if (!is_array($data)) { $data = []; }

    unset($data[$ip]);

    $json = json_encode($data);
    ftruncate($fh, 0);
    rewind($fh);
    fwrite($fh, $json);
    flock($fh, LOCK_UN);
    fclose($fh);
}

/**
 * Format a second count as a human-readable string (e.g. "14 minutes", "45 seconds").
 */
function rl_wait_label(int $seconds): string
{
    if ($seconds >= 120) {
        return (int) ceil($seconds / 60) . ' minutes';
    }
    if ($seconds >= 60) {
        return '1 minute';
    }
    return $seconds . ' second' . ($seconds !== 1 ? 's' : '');
}


// ----------------------------------------------------------------
// Private helpers
// ----------------------------------------------------------------

/** Load the attempts file into an array. */
function _rl_load(): array
{
    if (!file_exists(RL_FILE)) {
        return [];
    }
    $raw = file_get_contents(RL_FILE);
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

/** Write the attempts array to the file with an exclusive lock. */
function _rl_save(array $data): void
{
    file_put_contents(RL_FILE, json_encode($data), LOCK_EX);
}

/**
 * Remove stale entries (past both the window and any lockout expiry).
 * Keeps the file from growing indefinitely on a busy server.
 */
function _rl_cleanup(array $data): array
{
    $now = time();
    foreach ($data as $ip => $entry) {
        $window_end = (int)($entry['first_at']     ?? 0) + RL_WINDOW;
        $lock_end   = (int)($entry['locked_until'] ?? 0);
        if ($now > max($window_end, $lock_end)) {
            unset($data[$ip]);
        }
    }
    return $data;
}
