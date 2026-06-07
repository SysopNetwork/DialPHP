<?php
/**
 * DialPHP PHP Examples — Login Demo
 *
 * Demonstrates user authentication against The Major BBS via DialPHP.
 * Public page — no key requirement.
 *
 * Commands demonstrated:
 *   USERIDEXISTS  — confirm the user account exists before attempting auth
 *   AUTHUSER      — verify the password
 *   CURRENTCLASS  — retrieve the user's active class
 *   ISSUSPENDED   — check suspension status
 *   USERONLINE    — check whether user is currently logged into the BBS
 *   SYSTEMVARIABLE 5 — display total account count as a welcome stat
 *
 * Copyright (c) 2026 Mark Laudenbach
 * Sysop Network
 * Licensed under the MIT License
 */

// Load BBS connection configuration from the same directory
$config = require __DIR__ . '/config.php';

// Load the DialPHP client class and rate limiter
require_once __DIR__ . '/dialphp.php';
require_once __DIR__ . '/ratelimit.php';

// ----------------------------------------------------------------
// State variables
// ----------------------------------------------------------------
$error    = '';        // Error message to display
$result   = null;      // Array of query results after successful login
$userid   = '';        // Submitted user ID (preserved for form repopulation)
$accounts = null;      // Total BBS accounts (shown in header stat)

// ----------------------------------------------------------------
// Fetch total account count for the welcome stat bar
// This is a read-only public stat; failures are silently ignored.
// ----------------------------------------------------------------
try {
    $bbs      = new DialPHP($config['host'], $config['port'], $config['secret'], $config['timeout']);
    $accounts = $bbs->query('SYSTEMVARIABLE', ['5']);
    // Ignore the result if the variable is unsupported on this BBS build
    if (strpos($accounts, 'No such') !== false) {
        $accounts = null;
    }
} catch (DialPHPException $e) {
    $accounts = null;   // BBS not reachable — stat bar stays hidden
}

// ----------------------------------------------------------------
// Rate limiting — check before processing any login attempt
// ----------------------------------------------------------------
$clientIp = rl_ip();
$rl        = rl_check($clientIp);

// ----------------------------------------------------------------
// Handle login form submission
// ----------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!$rl['allowed']) {
        // IP is currently locked out — do not process the form at all
        $error = 'Too many failed login attempts. Please try again in '
               . rl_wait_label($rl['wait']) . '.';

    } else {
        // Sanitize inputs: strip leading/trailing whitespace from the user ID;
        // do NOT trim the password, as some passwords intentionally have spaces.
        $userid   = trim(strip_tags($_POST['userid']   ?? ''));
        $password = $_POST['password'] ?? '';

        if ($userid === '' || $password === '') {
            $error = 'Please enter both a User ID and password.';

        } else {
            try {
                $bbs = new DialPHP($config['host'], $config['port'], $config['secret'], $config['timeout']);

                // AUTHUSER returns 'Password is correct', 'Password is incorrect',
                // or 'No such user' — map all non-success responses to the same
                // generic error so callers cannot enumerate valid usernames.
                $auth = $bbs->query('AUTHUSER', [$userid, $password]);

                if ($auth === 'Password is correct') {

                    // Authentication succeeded — clear rate limit and gather profile
                    rl_clear($clientIp);

                    $result = [
                        'userid'     => $userid,
                        'auth'       => $auth,
                        'class'      => $bbs->query('CURRENTCLASS',    [$userid]),
                        'primary'    => $bbs->query('PRIMARYCLASS',    [$userid]),
                        'credits'    => $bbs->query('NUMBEROFCREDITS', [$userid]),
                        'days'       => $bbs->query('NUMBEROFDAYS',    [$userid]),
                        'last_login' => $bbs->query('LASTLOGIN',       [$userid]),
                        'created'    => $bbs->query('CREATIONDATE',    [$userid]),
                        'online'     => $bbs->query('USERONLINE',      [$userid]),
                        'suspended'  => $bbs->query('ISSUSPENDED',     [$userid]),
                        'has_master' => $bbs->query('HASMASTER',       [$userid]),
                    ];

                } else {
                    // Wrong password or no such user — same message for both
                    rl_fail($clientIp);
                    $error = 'Invalid credentials or insufficient permissions.';
                }

            } catch (DialPHPException $e) {
                // Connection or protocol failure — show a user-friendly message.
                // Do NOT record as a rate-limit failure (not the user's fault).
                $error = 'Unable to connect to the BBS. Please try again later.';
            }
        }
    }

    // Re-check the rate limit state after potentially recording a failure,
    // so the lockout message is shown immediately if the last attempt tripped it.
    $rl = rl_check($clientIp);
}

// ----------------------------------------------------------------
// Helper: render a YES/NO badge
// ----------------------------------------------------------------
function yesNoBadge(string $value, bool $invertColor = false): string
{
    $isYes = ($value === 'YES');
    if ($invertColor) { $isYes = !$isYes; } // suspended=YES should be red
    $class = $isYes ? 'badge-green' : 'badge-red';
    return '<span class="badge ' . $class . '">' . htmlspecialchars($value, ENT_QUOTES) . '</span>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Demo &mdash; DialPHP Examples</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<!-- Navigation -->
<nav class="nav">
    <a class="nav-brand" href="index.php">Dial<span>PHP</span></a>
    <a href="index.php">Home</a>
    <a href="login.php" class="active">Login Demo</a>
    <a href="stats.php">Statistics Demo</a>
    <a href="sysop.php">Sysop Panel Demo</a>
</nav>

<main class="page">

    <!-- ============================================================
         Header stat bar — shows total accounts if available
         ============================================================ -->
    <?php if ($accounts !== null): ?>
    <div style="background:#ddf4ff; border:1px solid rgba(84,174,255,.4); border-radius:6px; padding:10px 16px; margin-bottom:20px; font-size:13px; color:#0550ae;">
        The Major BBS currently has
        <strong class="mono"><?= htmlspecialchars($accounts, ENT_QUOTES) ?></strong>
        registered accounts.
    </div>
    <?php endif; ?>

    <div class="page-head">
        <h1>Login Demo</h1>
        <p>Enter your Major BBS credentials to verify them against the live BBS system.</p>
    </div>

    <!-- ============================================================
         Login form (shown until successful authentication)
         ============================================================ -->
    <?php if ($result === null): ?>

    <div style="max-width:400px;">

        <div class="box">
            <div class="box-head">Login Required</div>
            <div class="box-body">

                <?php if ($error !== ''): ?>
                <div class="flash flash-error"><?= htmlspecialchars($error, ENT_QUOTES) ?></div>
                <?php endif; ?>

                <form method="POST" action="login.php" autocomplete="off"
                      <?= (!$rl['allowed']) ? 'style="opacity:.5; pointer-events:none;"' : '' ?>>
                    <div class="form-row">
                        <label class="form-label" for="userid">User ID</label>
                        <input
                            class="form-input"
                            type="text"
                            id="userid"
                            name="userid"
                            value="<?= htmlspecialchars($userid, ENT_QUOTES) ?>"
                            maxlength="30"
                            autocomplete="username"
                            autofocus
                            required
                        >
                    </div>

                    <div class="form-row">
                        <label class="form-label" for="password">Password</label>
                        <input
                            class="form-input"
                            type="password"
                            id="password"
                            name="password"
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
            <div class="box-foot">
                Credentials are verified against the live BBS — they are never stored here.
            </div>
        </div>

        <p style="font-size:13px; color:#57606a; margin-top:12px;">
            Want to manage accounts?
            <a href="sysop.php">Sysop Panel Demo</a> &rarr;
        </p>

    </div>

    <?php else: ?>
    <!-- ============================================================
         Authentication succeeded — display user profile
         ============================================================ -->

    <div class="flash flash-success">
        Authentication successful. Password verified for
        <strong><?= htmlspecialchars($result['userid'], ENT_QUOTES) ?></strong>.
    </div>

    <div class="grid-2" style="margin-bottom:16px;">

        <!-- Account overview card -->
        <div class="box">
            <div class="box-head">Account Overview</div>
            <div class="box-body" style="padding:0;">
                <table class="tbl">
                    <tr>
                        <td class="lbl">User ID</td>
                        <td><strong class="mono"><?= htmlspecialchars($result['userid'], ENT_QUOTES) ?></strong></td>
                    </tr>
                    <tr>
                        <td class="lbl">Current Class</td>
                        <td><span class="badge badge-blue mono"><?= htmlspecialchars($result['class'], ENT_QUOTES) ?></span></td>
                    </tr>
                    <tr>
                        <td class="lbl">Primary Class</td>
                        <td><span class="mono"><?= htmlspecialchars($result['primary'], ENT_QUOTES) ?></span></td>
                    </tr>
                    <tr>
                        <td class="lbl">Credits</td>
                        <td><span class="mono"><?= htmlspecialchars($result['credits'], ENT_QUOTES) ?></span></td>
                    </tr>
                    <tr>
                        <td class="lbl">Days Remaining</td>
                        <td><span class="mono"><?= htmlspecialchars($result['days'], ENT_QUOTES) ?></span></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Status and dates card -->
        <div class="box">
            <div class="box-head">Status &amp; Dates</div>
            <div class="box-body" style="padding:0;">
                <table class="tbl">
                    <tr>
                        <td class="lbl">Currently Online</td>
                        <td><?= yesNoBadge($result['online']) ?></td>
                    </tr>
                    <tr>
                        <td class="lbl">Suspended</td>
                        <!-- Suspended=YES is bad, so invert the color -->
                        <td><?= yesNoBadge($result['suspended'], true) ?></td>
                    </tr>
                    <tr>
                        <td class="lbl">Has Master Key</td>
                        <td><?= yesNoBadge($result['has_master']) ?></td>
                    </tr>
                    <tr>
                        <td class="lbl">Last Login</td>
                        <td><span class="mono"><?= htmlspecialchars($result['last_login'], ENT_QUOTES) ?></span></td>
                    </tr>
                    <tr>
                        <td class="lbl">Account Created</td>
                        <td><span class="mono"><?= htmlspecialchars($result['created'], ENT_QUOTES) ?></span></td>
                    </tr>
                </table>
            </div>
        </div>

    </div>

    <!-- Commands used in this request -->
    <div class="box">
        <div class="box-head">Commands Executed</div>
        <div class="box-body" style="font-size:13px;">
            <p style="margin:0 0 8px; color:#57606a;">The following DialPHP commands were issued (each on a separate TCP connection):</p>
            <div class="result-box">AUTHUSER     <?= htmlspecialchars($result['userid'], ENT_QUOTES) ?>%%*****
CURRENTCLASS <?= htmlspecialchars($result['userid'], ENT_QUOTES) ?>    → <?= htmlspecialchars($result['class'], ENT_QUOTES) ?>

PRIMARYCLASS <?= htmlspecialchars($result['userid'], ENT_QUOTES) ?>    → <?= htmlspecialchars($result['primary'], ENT_QUOTES) ?>

NUMBEROFCREDITS <?= htmlspecialchars($result['userid'], ENT_QUOTES) ?> → <?= htmlspecialchars($result['credits'], ENT_QUOTES) ?>

NUMBEROFDAYS    <?= htmlspecialchars($result['userid'], ENT_QUOTES) ?> → <?= htmlspecialchars($result['days'], ENT_QUOTES) ?>

LASTLOGIN       <?= htmlspecialchars($result['userid'], ENT_QUOTES) ?> → <?= htmlspecialchars($result['last_login'], ENT_QUOTES) ?>

CREATIONDATE    <?= htmlspecialchars($result['userid'], ENT_QUOTES) ?> → <?= htmlspecialchars($result['created'], ENT_QUOTES) ?>

USERONLINE      <?= htmlspecialchars($result['userid'], ENT_QUOTES) ?> → <?= htmlspecialchars($result['online'], ENT_QUOTES) ?>

ISSUSPENDED     <?= htmlspecialchars($result['userid'], ENT_QUOTES) ?> → <?= htmlspecialchars($result['suspended'], ENT_QUOTES) ?>

HASMASTER       <?= htmlspecialchars($result['userid'], ENT_QUOTES) ?> → <?= htmlspecialchars($result['has_master'], ENT_QUOTES) ?></div>
        </div>
    </div>

    <!-- Try again / navigation -->
    <div style="margin-top:8px; display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
        <a href="login.php" class="btn btn-default">
            &#8592; Try Another User
        </a>
        <a href="sysop.php" class="btn btn-primary">
            Sysop Panel Demo &#8594;
        </a>
    </div>

    <?php endif; ?>

</main>

<footer class="footer">
    DialPHP PHP Examples &mdash; Copyright &copy; 2026 Mark Laudenbach &bull; Sysop Network &bull;
    <a href="https://github.com/sysopnetwork/dialphp" target="_blank">GitHub</a>
</footer>

</body>
</html>
