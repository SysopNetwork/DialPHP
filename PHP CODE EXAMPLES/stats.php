<?php
/**
 * DialPHP PHP Examples — System Statistics Demo
 *
 * Displays live system variables from The Major BBS using SYSTEMVARIABLE.
 * Results are cached to a local JSON file for 60 minutes to reduce
 * repeated connections to the BBS.
 *
 * Access control: requires the SYSOP key.
 * Session persists for the browser session; logout clears it.
 *
 * Commands used:
 *   AUTHUSER       — login verification
 *   HASKEY         — confirm the user holds the SYSOP key
 *   CURRENTCLASS   — display the logged-in user's class
 *   SYSTEMVARIABLE — retrieve each statistic
 *
 * Copyright (c) 2026 Mark Laudenbach
 * Sysop Network
 * Licensed under the MIT License
 */

// ----------------------------------------------------------------
// Secure session setup
// ----------------------------------------------------------------
session_set_cookie_params([
    'lifetime' => 0,           // Session cookie (expires when browser closes)
    'path'     => '/',
    'secure'   => true,        // HTTPS only — remove if running over plain HTTP in dev
    'httponly' => true,        // Not accessible via JavaScript
    'samesite' => 'Strict',
]);
session_start();

// Load BBS config, client, and rate limiter from the same directory
$config = require __DIR__ . '/config.php';
require_once __DIR__ . '/dialphp.php';
require_once __DIR__ . '/ratelimit.php';

// ----------------------------------------------------------------
// Constants
// ----------------------------------------------------------------
define('CACHE_FILE',       __DIR__ . '/data/stats_cache.json');  // Where to store cached stats
define('CACHE_DURATION',   3600);                  // Cache lifetime in seconds (60 min)

// ----------------------------------------------------------------
// State variables
// ----------------------------------------------------------------
$loggedIn  = false;     // Whether the user is authenticated
$authUser  = '';        // Username of the authenticated user
$authClass = '';        // Class of the authenticated user
$authError = '';        // Login form error message
$stats     = null;      // Fetched/cached statistics array

// ----------------------------------------------------------------
// Logout
// ----------------------------------------------------------------
if (isset($_GET['logout'])) {
    // Clear session data and destroy the session
    $_SESSION = [];
    session_destroy();
    // Redirect to avoid reprocessing the GET parameter on reload
    header('Location: stats.php');
    exit;
}

// ----------------------------------------------------------------
// Check existing session
// ----------------------------------------------------------------
if (!empty($_SESSION['stats_user'])) {

    $sessionUser = $_SESSION['stats_user'];

    // Re-validate on every page load — the BBS is the source of truth for sysop status
    try {
        $bbs       = new DialPHP($config['host'], $config['port'], $config['secret'], $config['timeout']);
        $hasMaster = $bbs->query('HASMASTER', [$sessionUser]);

        if ($hasMaster === 'YES') {
            $loggedIn  = true;
            $authUser  = $sessionUser;
            $authClass = $bbs->query('CURRENTCLASS', [$sessionUser]);
        } else {
            // Sysop privilege was revoked — force re-login
            $_SESSION = [];
            session_destroy();
            session_start();
            $authError = 'Your sysop access has been revoked. Please log in again.';
        }
    } catch (DialPHPException $e) {
        // BBS unavailable — keep the session but mark BBS as down
        $loggedIn  = true;
        $authUser  = $sessionUser;
        $authClass = $_SESSION['stats_class'] ?? 'Unknown';
    }
}

// ----------------------------------------------------------------
// Rate limiting
// ----------------------------------------------------------------
$clientIp = rl_ip();
$rl        = rl_check($clientIp);

// ----------------------------------------------------------------
// Handle login form submission
// ----------------------------------------------------------------
if (!$loggedIn && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_userid'])) {

    if (!$rl['allowed']) {
        $authError = 'Too many failed login attempts. Please try again in '
                   . rl_wait_label($rl['wait']) . '.';

    } else {
        $loginUser = trim(strip_tags($_POST['login_userid'] ?? ''));
        $loginPass = $_POST['login_password'] ?? '';

        if ($loginUser === '' || $loginPass === '') {
            $authError = 'Please enter both a User ID and password.';

        } else {
            try {
                $bbs  = new DialPHP($config['host'], $config['port'], $config['secret'], $config['timeout']);
                $auth = $bbs->query('AUTHUSER', [$loginUser, $loginPass]);

                if ($auth !== 'Password is correct') {
                    // Treat "no such user" and "wrong password" identically
                    rl_fail($clientIp);
                    $authError = 'Invalid credentials or insufficient permissions.';

                } else {
                    // Verify sysop privilege — use a generic message so a correct
                    // password does not get confirmed to unauthorized users
                    $hasMaster = $bbs->query('HASMASTER', [$loginUser]);

                    if ($hasMaster !== 'YES') {
                        rl_fail($clientIp);
                        $authError = 'Invalid credentials or insufficient permissions.';

                    } else {
                        // All checks passed
                        rl_clear($clientIp);
                        session_regenerate_id(true);

                        $_SESSION['stats_user']  = $loginUser;
                        $_SESSION['stats_class'] = $bbs->query('CURRENTCLASS', [$loginUser]);

                        header('Location: stats.php');
                        exit;
                    }
                }

            } catch (DialPHPException $e) {
                $authError = 'Cannot connect to the BBS. Please try again later.';
            }
        }

        // Refresh rate limit state after possibly recording a failure
        $rl = rl_check($clientIp);
    }
}

// ----------------------------------------------------------------
// Fetch statistics (cached or live)
// ----------------------------------------------------------------

/**
 * Query one SYSTEMVARIABLE and return its value.
 * Returns '—' if the variable is unsupported or the BBS is unreachable.
 */
function fetchVar(DialPHP $bbs, int $varNum): string
{
    try {
        $val = $bbs->query('SYSTEMVARIABLE', [(string)$varNum]);
        return (strpos($val, 'No such') !== false) ? '—' : $val;
    } catch (DialPHPException $e) {
        return '—';
    }
}

/**
 * Return stats from cache if fresh, or fetch live and refresh the cache.
 * Pass $force = true to bypass the cache.
 */
function loadStats(array $config, bool $force = false): array
{
    // Use cached data if it exists and is still within the cache window
    if (!$force && file_exists(CACHE_FILE)) {
        $cached = json_decode(file_get_contents(CACHE_FILE), true);
        if (is_array($cached) && isset($cached['timestamp'])) {
            $age = time() - $cached['timestamp'];
            if ($age < CACHE_DURATION) {
                $cached['from_cache'] = true;
                $cached['cache_age']  = $age;
                return $cached;
            }
        }
    }

    // Cache is stale or missing — fetch fresh data from the BBS
    $bbs  = new DialPHP($config['host'], $config['port'], $config['secret'], $config['timeout']);
    $data = [
        'users_online'   => fetchVar($bbs, 13),
        'total_accounts' => fetchVar($bbs, 5),
        'males'          => fetchVar($bbs, 7),
        'females'        => fetchVar($bbs, 6),
        'total_calls'    => fetchVar($bbs, 12),
        'messages'       => fetchVar($bbs, 3),
        'downloads'      => fetchVar($bbs, 1),
        'uploads'        => fetchVar($bbs, 2),
        'paid_credits'   => fetchVar($bbs, 10),
        'free_credits'   => fetchVar($bbs, 11),
        'timestamp'      => time(),
        'from_cache'     => false,
        'cache_age'      => 0,
    ];

    // Write to cache file
    file_put_contents(CACHE_FILE, json_encode($data, JSON_PRETTY_PRINT));

    return $data;
}

// Only load stats when the user is authenticated
if ($loggedIn) {
    $forceRefresh = (isset($_GET['refresh']) && $_GET['refresh'] === '1');
    try {
        $stats = loadStats($config, $forceRefresh);
    } catch (DialPHPException $e) {
        // BBS is unreachable — try to serve stale cache rather than fail entirely
        if (file_exists(CACHE_FILE)) {
            $stats = json_decode(file_get_contents(CACHE_FILE), true);
            $stats['from_cache'] = true;
            $stats['stale']      = true;
        }
    }
}

// Helper: format seconds as "X min Y sec ago"
function ageLabel(int $seconds): string
{
    if ($seconds < 60) return $seconds . 's ago';
    return floor($seconds / 60) . 'm ' . ($seconds % 60) . 's ago';
}

// Helper: minutes until cache expires
function minutesUntilExpiry(int $age): string
{
    $remaining = CACHE_DURATION - $age;
    return max(0, (int) ceil($remaining / 60)) . ' min';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Statistics Demo &mdash; DialPHP Examples</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<!-- Navigation -->
<nav class="nav">
    <a class="nav-brand" href="index.php">Dial<span>PHP</span></a>
    <a href="index.php">Home</a>
    <a href="login.php">Login Demo</a>
    <a href="stats.php" class="active">Statistics Demo</a>
    <a href="sysop.php">Sysop Panel Demo</a>
    <?php if ($loggedIn): ?>
    <div class="spacer"></div>
    <div class="nav-user">
        <strong><?= htmlspecialchars($authUser, ENT_QUOTES) ?></strong>
        <a href="?logout=1">Logout</a>
    </div>
    <?php endif; ?>
</nav>

<main class="page">

<?php if ($loggedIn && $stats !== null): ?>
    <!-- ==================================================================
         AUTHENTICATED VIEW — statistics dashboard
         ================================================================== -->

    <!-- User bar -->
    <div class="user-bar">
        <div class="ub-left">
            Logged in as <strong><?= htmlspecialchars($authUser, ENT_QUOTES) ?></strong>
        </div>
        <a href="?logout=1" class="btn btn-default btn-sm">Logout</a>
    </div>

    <div class="page-head">
        <h1>System Statistics Demo</h1>
        <p>Live BBS system variables &mdash; fetched via SYSTEMVARIABLE command.</p>
    </div>

    <!-- Cache status notice -->
    <?php if (!empty($stats['stale'])): ?>
    <div class="flash flash-warn">
        BBS is unreachable. Showing stale cached data from
        <?= htmlspecialchars(date('g:i A', $stats['timestamp']), ENT_QUOTES) ?>.
    </div>
    <?php elseif ($stats['from_cache']): ?>
    <div class="flash flash-info">
        Showing cached data &mdash; fetched <?= ageLabel((int)$stats['cache_age']) ?>.
        Refreshes automatically in <?= minutesUntilExpiry((int)$stats['cache_age']) ?>.
        &nbsp;<a href="?refresh=1">Force refresh now</a>
    </div>
    <?php else: ?>
    <div class="flash flash-success">
        Live data fetched from BBS. Results cached for 60 minutes.
    </div>
    <?php endif; ?>

    <!-- Top row: key stats -->
    <div class="grid-4" style="margin-bottom:16px;">
        <div class="stat">
            <div class="stat-label">Users Online</div>
            <div class="stat-value"><?= htmlspecialchars($stats['users_online'], ENT_QUOTES) ?></div>
            <div class="stat-sub">SYSTEMVARIABLE 13</div>
        </div>
        <div class="stat">
            <div class="stat-label">Total Accounts</div>
            <div class="stat-value"><?= htmlspecialchars($stats['total_accounts'], ENT_QUOTES) ?></div>
            <div class="stat-sub">SYSTEMVARIABLE 5</div>
        </div>
        <div class="stat">
            <div class="stat-label">Total Calls</div>
            <div class="stat-value"><?= htmlspecialchars($stats['total_calls'], ENT_QUOTES) ?></div>
            <div class="stat-sub">SYSTEMVARIABLE 12</div>
        </div>
        <div class="stat">
            <div class="stat-label">Messages Posted</div>
            <div class="stat-value"><?= htmlspecialchars($stats['messages'], ENT_QUOTES) ?></div>
            <div class="stat-sub">SYSTEMVARIABLE 3</div>
        </div>
    </div>

    <!-- Demographics -->
    <div class="grid-2" style="margin-bottom:16px;">
        <div class="box">
            <div class="box-head">Demographics</div>
            <div class="box-body" style="padding:0;">
                <table class="tbl">
                    <tr>
                        <td class="lbl">Male Accounts</td>
                        <td class="mono"><?= htmlspecialchars($stats['males'], ENT_QUOTES) ?></td>
                    </tr>
                    <tr>
                        <td class="lbl">Female Accounts</td>
                        <td class="mono"><?= htmlspecialchars($stats['females'], ENT_QUOTES) ?></td>
                    </tr>
                </table>
            </div>
        </div>
        <div class="box">
            <div class="box-head">File Activity</div>
            <div class="box-body" style="padding:0;">
                <table class="tbl">
                    <tr>
                        <td class="lbl">Total Downloads</td>
                        <td class="mono"><?= htmlspecialchars($stats['downloads'], ENT_QUOTES) ?></td>
                    </tr>
                    <tr>
                        <td class="lbl">Total Uploads</td>
                        <td class="mono"><?= htmlspecialchars($stats['uploads'], ENT_QUOTES) ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Credits -->
    <div class="box">
        <div class="box-head">Credit Statistics Demo</div>
        <div class="box-body" style="padding:0;">
            <table class="tbl">
                <tr>
                    <td class="lbl">Paid Credits Posted</td>
                    <td class="mono"><?= htmlspecialchars($stats['paid_credits'], ENT_QUOTES) ?></td>
                </tr>
                <tr>
                    <td class="lbl">Free Credits Posted</td>
                    <td class="mono"><?= htmlspecialchars($stats['free_credits'], ENT_QUOTES) ?></td>
                </tr>
            </table>
        </div>
        <div class="box-foot">
            Stats cached at <?= htmlspecialchars(date('g:i:s A \o\n F j, Y', $stats['timestamp']), ENT_QUOTES) ?>
        </div>
    </div>

    <!-- Actions -->
    <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:8px;">
        <a href="?refresh=1" class="btn btn-default"
           onclick="return confirm('Fetch fresh statistics from the BBS now?')">
            &#8635; Force Refresh
        </a>
        <a href="sysop.php" class="btn btn-primary">Sysop Panel Demo &rarr;</a>
    </div>

<?php else: ?>
    <!-- ==================================================================
         UNAUTHENTICATED VIEW — login form
         ================================================================== -->

    <div class="page-head">
        <h1>System Statistics Demo</h1>
        <p>BBS system variables with 60-minute caching. Requires sysop privileges.</p>
    </div>

    <div style="max-width:400px;">

        <div class="box">
            <div class="box-head">Login Required</div>
            <div class="box-body">

                <?php if ($authError !== ''): ?>
                <div class="flash flash-error"><?= htmlspecialchars($authError, ENT_QUOTES) ?></div>
                <?php endif; ?>

                <div class="flash flash-info" style="margin-bottom:16px;">
                    Only accounts with sysop privileges can access this page.
                </div>

                <form method="POST" action="stats.php" autocomplete="off"
                      <?= (!$rl['allowed']) ? 'style="opacity:.5; pointer-events:none;"' : '' ?>>
                    <div class="form-row">
                        <label class="form-label" for="login_userid">User ID</label>
                        <input
                            class="form-input"
                            type="text"
                            id="login_userid"
                            name="login_userid"
                            maxlength="30"
                            autocomplete="username"
                            autofocus
                            required
                        >
                    </div>
                    <div class="form-row">
                        <label class="form-label" for="login_password">Password</label>
                        <input
                            class="form-input"
                            type="password"
                            id="login_password"
                            name="login_password"
                            maxlength="128"
                            autocomplete="current-password"
                            required
                        >
                    </div>
                    <button type="submit" class="btn btn-primary btn-full" style="margin-top:4px;">
                        Login
                    </button>
                </form>

            </div>
        </div>

        <p style="font-size:13px; color:#57606a; margin-top:12px;">
            <a href="login.php">&larr; Back to login demo</a>
        </p>

    </div>

<?php endif; ?>

</main>

<footer class="footer">
    DialPHP PHP Examples &mdash; Copyright &copy; 2026 Mark Laudenbach &bull; Sysop Network &bull;
    <a href="https://github.com/sysopnetwork/dialphp" target="_blank">GitHub</a>
</footer>

</body>
</html>
