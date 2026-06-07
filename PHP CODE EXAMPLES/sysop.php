<?php
/**
 * DialPHP PHP Examples — Sysop Panel Demo
 *
 * Administrative interface demonstrating all 24 supported DialPHP commands.
 * Requires the SYSOP key. Results are shown inline after each action.
 *
 * Sections:
 *   1. User Lookup     — full profile via multiple read commands
 *   2. Key Management  — HASKEY, GIVEKEY, TAKEKEY
 *   3. Credits & Days  — GIVECREDITS, GIVEDAYS
 *   4. Class Switch    — SWITCHCLASS
 *   5. Account Actions — SUSPENDUSER, UNSUSPENDUSER, DELETEUSER, UNDELETEUSER
 *   6. Update Field    — UPDATEUSERFIELD (edit individual user record fields)
 *   7. Update Field    — UPDATEUSERFIELD (edit individual user record fields)
 *
 * Security notes:
 *   - Session-based authentication; SYSOP key re-validated on every page load.
 *   - All admin forms carry a CSRF token to prevent cross-site request forgery.
 *   - All output is run through htmlspecialchars before rendering.
 *   - POST-Redirect-GET pattern prevents duplicate form submissions on reload.
 *
 * Copyright (c) 2026 Mark Laudenbach
 * Sysop Network
 * Licensed under the MIT License
 */

// ----------------------------------------------------------------
// Secure session setup
// ----------------------------------------------------------------
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => true,        // Remove if running over plain HTTP in development
    'httponly' => true,
    'samesite' => 'Strict',
]);
session_start();

$config = require __DIR__ . '/config.php';
require_once __DIR__ . '/dialphp.php';
require_once __DIR__ . '/ratelimit.php';

// ----------------------------------------------------------------
// Constants
// ----------------------------------------------------------------
// (no key-name constant needed — access is checked via HASMASTER)

// ----------------------------------------------------------------
// State variables
// ----------------------------------------------------------------
$loggedIn   = false;
$authUser   = '';
$authClass  = '';
$authError  = '';

// Result from the last executed admin action (shown after PRG redirect)
$actionResult = null;    // ['type' => 'success'|'error', 'label' => '...', 'value' => '...']

// ----------------------------------------------------------------
// CSRF token — generated once per session, validated on every POST
// ----------------------------------------------------------------
if (empty($_SESSION['sysop_csrf'])) {
    $_SESSION['sysop_csrf'] = bin2hex(random_bytes(16));
}
$csrfToken = $_SESSION['sysop_csrf'];

// ----------------------------------------------------------------
// Logout
// ----------------------------------------------------------------
if (isset($_GET['logout'])) {
    $_SESSION = [];
    session_destroy();
    header('Location: sysop.php');
    exit;
}

// ----------------------------------------------------------------
// Retrieve and clear any flash result stored by a previous POST
// ----------------------------------------------------------------
if (!empty($_SESSION['sysop_flash'])) {
    $actionResult = $_SESSION['sysop_flash'];
    unset($_SESSION['sysop_flash']);
}

// ----------------------------------------------------------------
// Validate existing session
// ----------------------------------------------------------------
if (!empty($_SESSION['sysop_user'])) {

    $sessionUser = $_SESSION['sysop_user'];

    try {
        $bbs       = new DialPHP($config['host'], $config['port'], $config['secret'], $config['timeout']);
        $hasMaster = $bbs->query('HASMASTER', [$sessionUser]);

        if ($hasMaster === 'YES') {
            $loggedIn  = true;
            $authUser  = $sessionUser;
            $authClass = $bbs->query('CURRENTCLASS', [$sessionUser]);
        } else {
            // Sysop privilege was revoked — clear session
            $_SESSION = [];
            session_destroy();
            session_start();
            $authError = 'Your sysop access has been revoked. Please log in again.';
        }
    } catch (DialPHPException $e) {
        // Preserve session if BBS is temporarily unreachable
        $loggedIn  = true;
        $authUser  = $sessionUser;
        $authClass = $_SESSION['sysop_class'] ?? 'Unknown';
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
                    // Identical message for "no such user" and "wrong password"
                    rl_fail($clientIp);
                    $authError = 'Invalid credentials or insufficient permissions.';
                } else {
                    // Verify sysop privilege — generic message so a correct password
                    // is not confirmed to unauthorized users
                    $hasMaster = $bbs->query('HASMASTER', [$loginUser]);
                    if ($hasMaster !== 'YES') {
                        rl_fail($clientIp);
                        $authError = 'Invalid credentials or insufficient permissions.';
                    } else {
                        rl_clear($clientIp);
                        session_regenerate_id(true);
                        $_SESSION['sysop_user']  = $loginUser;
                        $_SESSION['sysop_class'] = $bbs->query('CURRENTCLASS', [$loginUser]);
                        header('Location: sysop.php');
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
// Handle admin action forms (POST-Redirect-GET pattern)
// All actions require:
//   - Active authenticated session
//   - Valid CSRF token
//   - An 'action' field identifying which command to run
// ----------------------------------------------------------------
if ($loggedIn
    && $_SERVER['REQUEST_METHOD'] === 'POST'
    && !isset($_POST['login_userid'])
) {
    // Validate CSRF token
    if (empty($_POST['csrf']) || !hash_equals($csrfToken, $_POST['csrf'])) {
        $_SESSION['sysop_flash'] = [
            'type'  => 'error',
            'label' => 'Security',
            'value' => 'CSRF token mismatch. Form expired — please try again.',
        ];
        header('Location: sysop.php');
        exit;
    }

    $action = $_POST['action'] ?? '';

    // Sanitize every text input: trim whitespace, strip HTML tags
    $userid    = str_replace(["\r", "\n"], '', trim(strip_tags($_POST['userid']    ?? '')));
    $keyname   = str_replace(["\r", "\n"], '', trim(strip_tags(strtoupper($_POST['keyname']   ?? ''))));
    $classname = str_replace(["\r", "\n"], '', trim(strip_tags(strtoupper($_POST['classname'] ?? ''))));
    $amount    = str_replace(["\r", "\n"], '', trim(strip_tags($_POST['amount']    ?? '')));
    $days      = str_replace(["\r", "\n"], '', trim(strip_tags($_POST['days']      ?? '')));
    $fieldname = str_replace(["\r", "\n"], '', trim(strip_tags($_POST['fieldname'] ?? '')));
    $newvalue  = str_replace(["\r", "\n"], '', trim(strip_tags($_POST['newvalue']  ?? '')));
    $message   = str_replace(["\r", "\n"], '', trim(strip_tags($_POST['message']   ?? '')));

    $flashLabel = '';
    $flashValue = '';
    $flashType  = 'success';

    try {
        $bbs = new DialPHP($config['host'], $config['port'], $config['secret'], $config['timeout']);

        switch ($action) {

            // ---- User Lookup (read-only) --------------------------------
            case 'lookup':
                if ($userid === '') { throw new InvalidArgumentException('User ID is required.'); }
                $exists = $bbs->query('USERIDEXISTS', [$userid]);
                if ($exists !== 'YES') {
                    $flashType  = 'error';
                    $flashLabel = 'USERIDEXISTS';
                    $flashValue = 'User "' . $userid . '" does not exist on this BBS.';
                } else {
                    // Fetch the full profile and store it as structured data for display
                    $profile = [
                        'User ID'       => $userid,
                        'Current Class' => $bbs->query('CURRENTCLASS',    [$userid]),
                        'Primary Class' => $bbs->query('PRIMARYCLASS',    [$userid]),
                        'Credits'       => $bbs->query('NUMBEROFCREDITS', [$userid]),
                        'Days Remaining'=> $bbs->query('NUMBEROFDAYS',    [$userid]),
                        'Last Login'    => $bbs->query('LASTLOGIN',       [$userid]),
                        'Created'       => $bbs->query('CREATIONDATE',    [$userid]),
                        'Online'        => $bbs->query('USERONLINE',      [$userid]),
                        'Suspended'     => $bbs->query('ISSUSPENDED',     [$userid]),
                        'Has Master'    => $bbs->query('HASMASTER',       [$userid]),
                    ];
                    $flashType  = 'profile';   // Special type for table rendering
                    $flashLabel = 'User Profile: ' . $userid;
                    $flashValue = json_encode($profile);
                }
                break;

            // ---- Key Management -----------------------------------------
            case 'haskey':
                if ($userid === '' || $keyname === '') { throw new InvalidArgumentException('User ID and Key Name are required.'); }
                $flashLabel = "HASKEY {$keyname} {$userid}";
                $flashValue = $bbs->query('HASKEY', [$keyname, $userid]);
                break;

            case 'givekey':
                if ($userid === '' || $keyname === '') { throw new InvalidArgumentException('User ID and Key Name are required.'); }
                $flashLabel = "GIVEKEY {$keyname} {$userid}";
                $flashValue = $bbs->query('GIVEKEY', [$keyname, $userid]);
                break;

            case 'takekey':
                if ($userid === '' || $keyname === '') { throw new InvalidArgumentException('User ID and Key Name are required.'); }
                $flashLabel = "TAKEKEY {$keyname} {$userid}";
                $flashValue = $bbs->query('TAKEKEY', [$keyname, $userid]);
                break;

            // ---- Credits & Days -----------------------------------------
            case 'givecredits':
                if ($userid === '' || $amount === '') { throw new InvalidArgumentException('User ID and Amount are required.'); }
                if (!is_numeric($amount)) { throw new InvalidArgumentException('Amount must be a number.'); }
                $flashLabel = "GIVECREDITS {$amount} {$userid}";
                $flashValue = $bbs->query('GIVECREDITS', [$amount, $userid]);
                break;

            case 'givedays':
                if ($userid === '' || $days === '') { throw new InvalidArgumentException('User ID and Days are required.'); }
                if (!is_numeric($days)) { throw new InvalidArgumentException('Days must be a number.'); }
                $flashLabel = "GIVEDAYS {$days} {$userid}";
                $flashValue = $bbs->query('GIVEDAYS', [$days, $userid]);
                break;

            // ---- Class Management ----------------------------------------
            case 'switchclass':
                if ($userid === '' || $classname === '') { throw new InvalidArgumentException('User ID and Class Name are required.'); }
                $flashLabel = "SWITCHCLASS {$classname} {$userid}";
                $flashValue = $bbs->query('SWITCHCLASS', [$classname, $userid]);
                break;

            // ---- Account Actions (destructive — require confirmation checkbox)
            case 'suspenduser':
                if ($userid === '') { throw new InvalidArgumentException('User ID is required.'); }
                if (empty($_POST['confirmed'])) { throw new InvalidArgumentException('You must check the confirmation box for this action.'); }
                $flashLabel = "SUSPENDUSER {$userid}";
                $flashValue = $bbs->query('SUSPENDUSER', [$userid]);
                break;

            case 'unsuspenduser':
                if ($userid === '') { throw new InvalidArgumentException('User ID is required.'); }
                $flashLabel = "UNSUSPENDUSER {$userid}";
                $flashValue = $bbs->query('UNSUSPENDUSER', [$userid]);
                break;

            case 'deleteuser':
                if ($userid === '') { throw new InvalidArgumentException('User ID is required.'); }
                if (empty($_POST['confirmed'])) { throw new InvalidArgumentException('You must check the confirmation box for this action.'); }
                $flashLabel = "DELETEUSER {$userid}";
                $flashValue = $bbs->query('DELETEUSER', [$userid]);
                break;

            case 'undeleteuser':
                if ($userid === '') { throw new InvalidArgumentException('User ID is required.'); }
                $flashLabel = "UNDELETEUSER {$userid}";
                $flashValue = $bbs->query('UNDELETEUSER', [$userid]);
                break;

            // ---- Update User Field ---------------------------------------
            case 'updateuserfield':
                if ($userid === '' || $fieldname === '') { throw new InvalidArgumentException('User ID and Field Name are required.'); }
                // Format: UPDATEUSERFIELD fieldname userid%%newvalue
                $flashLabel = "UPDATEUSERFIELD {$fieldname} {$userid}%%{$newvalue}";
                $flashValue = $bbs->query('UPDATEUSERFIELD', [$fieldname, $userid, $newvalue]);
                break;

            default:
                $flashType  = 'error';
                $flashLabel = 'Error';
                $flashValue = 'Unknown action.';
        }

    } catch (InvalidArgumentException $e) {
        $flashType  = 'error';
        $flashLabel = 'Validation';
        $flashValue = $e->getMessage();
    } catch (DialPHPException $e) {
        $flashType  = 'error';
        $flashLabel = 'BBS Error';
        $flashValue = $e->getMessage();
    }

    // Store in session and redirect (PRG)
    $_SESSION['sysop_flash'] = [
        'type'  => $flashType,
        'label' => $flashLabel,
        'value' => $flashValue,
    ];
    // Rotate CSRF token after every action to limit the replay window
    $_SESSION['sysop_csrf'] = bin2hex(random_bytes(16));
    header('Location: sysop.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sysop Panel Demo &mdash; DialPHP Examples</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Sysop panel-specific styles */
        .action-section {
            background: #fff;
            border: 1px solid #d0d7de;
            border-radius: 6px;
            margin-bottom: 20px;
            overflow: hidden;
        }
        .action-section .section-head {
            background: #f6f8fa;
            border-bottom: 1px solid #d0d7de;
            padding: 10px 16px;
            font-size: 14px;
            font-weight: 600;
            color: #1f2328;
        }
        .action-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 0;
        }
        .action-cell {
            padding: 16px;
            border-right: 1px solid #f0f0f0;
        }
        .action-cell:last-child { border-right: none; }
        .action-cell h4 {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #57606a;
            margin: 0 0 10px;
        }
        @media (max-width: 640px) {
            .action-grid { grid-template-columns: 1fr; }
            .action-cell { border-right: none; border-bottom: 1px solid #f0f0f0; }
            .action-cell:last-child { border-bottom: none; }
        }
    </style>
</head>
<body>

<!-- Navigation -->
<nav class="nav">
    <a class="nav-brand" href="index.php">Dial<span>PHP</span></a>
    <a href="index.php">Home</a>
    <a href="login.php">Login Demo</a>
    <a href="stats.php">Statistics Demo</a>
    <a href="sysop.php" class="active">Sysop Panel Demo</a>
    <?php if ($loggedIn): ?>
    <div class="spacer"></div>
    <div class="nav-user">
        <strong><?= htmlspecialchars($authUser, ENT_QUOTES) ?></strong>
        <a href="?logout=1">Logout</a>
    </div>
    <?php endif; ?>
</nav>

<main class="page">

<?php if ($loggedIn): ?>
    <!-- ==================================================================
         AUTHENTICATED VIEW — admin panel
         ================================================================== -->

    <!-- User bar -->
    <div class="user-bar">
        <div class="ub-left">
            Logged in as <strong><?= htmlspecialchars($authUser, ENT_QUOTES) ?></strong>
        </div>
        <a href="?logout=1" class="btn btn-default btn-sm">Logout</a>
    </div>

    <div class="page-head">
        <h1>Sysop Panel Demo</h1>
        <p>Account management tools. All actions modify live BBS data. Use with care.</p>
    </div>

    <!-- ----------------------------------------------------------------
         Action result (shown after PRG redirect)
         ---------------------------------------------------------------- -->
    <?php if ($actionResult !== null): ?>
        <?php if ($actionResult['type'] === 'profile'): ?>
            <!-- Profile lookup result — rendered as a table -->
            <?php $profile = json_decode($actionResult['value'], true); ?>
            <div class="flash flash-info" style="flex-direction:column; align-items:flex-start;">
                <strong style="margin-bottom:8px;">
                    <?= htmlspecialchars($actionResult['label'], ENT_QUOTES) ?>
                </strong>
                <table class="tbl" style="width:100%; margin-top:0;">
                    <?php foreach ($profile as $label => $val): ?>
                    <tr>
                        <td style="width:40%; color:#57606a; font-weight:500; padding:5px 0; font-size:13px;">
                            <?= htmlspecialchars($label, ENT_QUOTES) ?>
                        </td>
                        <td style="font-size:13px; padding:5px 0;">
                            <?php
                            // Apply color badges to YES/NO values
                            if ($val === 'YES') {
                                $badgeClass = in_array($label, ['Suspended']) ? 'badge-red' : 'badge-green';
                                echo '<span class="badge ' . $badgeClass . '">YES</span>';
                            } elseif ($val === 'NO') {
                                $badgeClass = in_array($label, ['Suspended']) ? 'badge-green' : 'badge-gray';
                                echo '<span class="badge ' . $badgeClass . '">NO</span>';
                            } else {
                                echo '<span class="mono">' . htmlspecialchars($val, ENT_QUOTES) . '</span>';
                            }
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>

        <?php elseif ($actionResult['type'] === 'error'): ?>
            <div class="flash flash-error">
                <div>
                    <strong><?= htmlspecialchars($actionResult['label'], ENT_QUOTES) ?>:</strong>
                    <?= htmlspecialchars($actionResult['value'], ENT_QUOTES) ?>
                </div>
            </div>

        <?php else: ?>
            <div class="flash flash-success">
                <div>
                    <span class="code"><?= htmlspecialchars($actionResult['label'], ENT_QUOTES) ?></span>
                    &rarr;
                    <strong class="mono"><?= htmlspecialchars($actionResult['value'], ENT_QUOTES) ?></strong>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Destructive-actions warning -->
    <div class="flash flash-warn">
        <div>
            <strong>Warning:</strong> Actions on this page directly modify live BBS user accounts.
            Destructive actions (Suspend, Delete) require confirmation. Deleted accounts can be recovered with Undelete.
        </div>
    </div>

    <!-- ==============================================================
         SECTION 1: User Lookup
         ============================================================== -->
    <div class="section-label">1 &mdash; User Lookup</div>
    <div class="action-section">
        <div class="section-head">User Profile</div>
        <div style="padding:16px;">
            <p style="font-size:13px; color:#57606a; margin:0 0 12px;">
                Queries the full account profile for any user ID.
                Uses: USERIDEXISTS, CURRENTCLASS, PRIMARYCLASS, NUMBEROFCREDITS, NUMBEROFDAYS,
                LASTLOGIN, CREATIONDATE, USERONLINE, ISSUSPENDED, HASMASTER.
            </p>
            <form method="POST" action="sysop.php">
                <input type="hidden" name="csrf"   value="<?= htmlspecialchars($csrfToken, ENT_QUOTES) ?>">
                <input type="hidden" name="action" value="lookup">
                <div class="form-inline">
                    <div class="form-row">
                        <label class="form-label" for="lookup_userid">User ID</label>
                        <input class="form-input" type="text" id="lookup_userid" name="userid" maxlength="30" placeholder="e.g. Sysop" required>
                    </div>
                    <div class="form-row" style="min-width:auto; flex:0; align-self:flex-end;">
                        <button type="submit" class="btn btn-primary">Lookup</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- ==============================================================
         SECTION 2: Key Management
         ============================================================== -->
    <div class="section-label">2 &mdash; Key Management</div>
    <div class="action-section">
        <div class="section-head">HASKEY / GIVEKEY / TAKEKEY</div>
        <div class="action-grid">

            <!-- HASKEY -->
            <div class="action-cell">
                <h4>Check Key</h4>
                <form method="POST" action="sysop.php">
                    <input type="hidden" name="csrf"   value="<?= htmlspecialchars($csrfToken, ENT_QUOTES) ?>">
                    <input type="hidden" name="action" value="haskey">
                    <div class="form-row">
                        <label class="form-label" for="haskey_userid">User ID</label>
                        <input class="form-input" type="text" id="haskey_userid" name="userid" maxlength="30" required>
                    </div>
                    <div class="form-row">
                        <label class="form-label" for="haskey_key">Key Name</label>
                        <input class="form-input" type="text" id="haskey_key" name="keyname" maxlength="30" placeholder="e.g. NORMAL" required>
                    </div>
                    <button type="submit" class="btn btn-default btn-sm">HASKEY</button>
                </form>
            </div>

            <!-- GIVEKEY -->
            <div class="action-cell">
                <h4>Give Key</h4>
                <form method="POST" action="sysop.php">
                    <input type="hidden" name="csrf"   value="<?= htmlspecialchars($csrfToken, ENT_QUOTES) ?>">
                    <input type="hidden" name="action" value="givekey">
                    <div class="form-row">
                        <label class="form-label" for="givekey_userid">User ID</label>
                        <input class="form-input" type="text" id="givekey_userid" name="userid" maxlength="30" required>
                    </div>
                    <div class="form-row">
                        <label class="form-label" for="givekey_key">Key Name</label>
                        <input class="form-input" type="text" id="givekey_key" name="keyname" maxlength="30" placeholder="e.g. NORMAL" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">GIVEKEY</button>
                </form>
            </div>

            <!-- TAKEKEY -->
            <div class="action-cell">
                <h4>Take Key</h4>
                <form method="POST" action="sysop.php">
                    <input type="hidden" name="csrf"   value="<?= htmlspecialchars($csrfToken, ENT_QUOTES) ?>">
                    <input type="hidden" name="action" value="takekey">
                    <div class="form-row">
                        <label class="form-label" for="takekey_userid">User ID</label>
                        <input class="form-input" type="text" id="takekey_userid" name="userid" maxlength="30" required>
                    </div>
                    <div class="form-row">
                        <label class="form-label" for="takekey_key">Key Name</label>
                        <input class="form-input" type="text" id="takekey_key" name="keyname" maxlength="30" placeholder="e.g. DEMO" required>
                    </div>
                    <button type="submit" class="btn btn-warning btn-sm">TAKEKEY</button>
                </form>
            </div>

        </div>
    </div>

    <!-- ==============================================================
         SECTION 3: Credits & Days
         ============================================================== -->
    <div class="section-label">3 &mdash; Credits &amp; Days</div>
    <div class="action-section">
        <div class="section-head">GIVECREDITS / GIVEDAYS</div>
        <div class="action-grid">

            <!-- GIVECREDITS -->
            <div class="action-cell">
                <h4>Give / Remove Credits</h4>
                <form method="POST" action="sysop.php">
                    <input type="hidden" name="csrf"   value="<?= htmlspecialchars($csrfToken, ENT_QUOTES) ?>">
                    <input type="hidden" name="action" value="givecredits">
                    <div class="form-row">
                        <label class="form-label" for="cred_userid">User ID</label>
                        <input class="form-input" type="text" id="cred_userid" name="userid" maxlength="30" required>
                    </div>
                    <div class="form-row">
                        <label class="form-label" for="cred_amount">
                            Amount
                            <span class="hint">(negative to subtract)</span>
                        </label>
                        <input class="form-input" type="number" id="cred_amount" name="amount" placeholder="e.g. 500 or -100" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">GIVECREDITS</button>
                </form>
            </div>

            <!-- GIVEDAYS -->
            <div class="action-cell">
                <h4>Give / Remove Days</h4>
                <p style="font-size:12px; color:#57606a; margin:0 0 10px;">Only affects classes with expiration tracking.</p>
                <form method="POST" action="sysop.php">
                    <input type="hidden" name="csrf"   value="<?= htmlspecialchars($csrfToken, ENT_QUOTES) ?>">
                    <input type="hidden" name="action" value="givedays">
                    <div class="form-row">
                        <label class="form-label" for="days_userid">User ID</label>
                        <input class="form-input" type="text" id="days_userid" name="userid" maxlength="30" required>
                    </div>
                    <div class="form-row">
                        <label class="form-label" for="days_num">
                            Days
                            <span class="hint">(negative to subtract)</span>
                        </label>
                        <input class="form-input" type="number" id="days_num" name="days" placeholder="e.g. 30 or -7" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">GIVEDAYS</button>
                </form>
            </div>

        </div>
    </div>

    <!-- ==============================================================
         SECTION 4: Class Management
         ============================================================== -->
    <div class="section-label">4 &mdash; Class Management</div>
    <div class="action-section">
        <div class="section-head">SWITCHCLASS</div>
        <div style="padding:16px;">
            <p style="font-size:13px; color:#57606a; margin:0 0 12px;">
                Switch a user's current class. The user must already be a member of the target class.
            </p>
            <form method="POST" action="sysop.php">
                <input type="hidden" name="csrf"   value="<?= htmlspecialchars($csrfToken, ENT_QUOTES) ?>">
                <input type="hidden" name="action" value="switchclass">
                <div class="form-inline">
                    <div class="form-row">
                        <label class="form-label" for="sc_userid">User ID</label>
                        <input class="form-input" type="text" id="sc_userid" name="userid" maxlength="30" required>
                    </div>
                    <div class="form-row">
                        <label class="form-label" for="sc_class">Target Class</label>
                        <input class="form-input" type="text" id="sc_class" name="classname" maxlength="30" placeholder="e.g. USER" required>
                    </div>
                    <div class="form-row" style="min-width:auto; flex:0; align-self:flex-end;">
                        <button type="submit" class="btn btn-primary">SWITCHCLASS</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- ==============================================================
         SECTION 5: Account Actions (destructive)
         ============================================================== -->
    <div class="section-label">5 &mdash; Account Actions</div>
    <div class="action-section">
        <div class="section-head">SUSPENDUSER / UNSUSPENDUSER / DELETEUSER / UNDELETEUSER</div>
        <div class="action-grid">

            <!-- SUSPENDUSER -->
            <div class="action-cell">
                <h4>Suspend</h4>
                <p style="font-size:12px; color:#57606a; margin:0 0 10px;">Prevents user from logging in.</p>
                <form method="POST" action="sysop.php">
                    <input type="hidden" name="csrf"   value="<?= htmlspecialchars($csrfToken, ENT_QUOTES) ?>">
                    <input type="hidden" name="action" value="suspenduser">
                    <div class="form-row">
                        <label class="form-label" for="susp_userid">User ID</label>
                        <input class="form-input" type="text" id="susp_userid" name="userid" maxlength="30" required>
                    </div>
                    <div class="form-row" style="display:flex; gap:8px; align-items:center; font-size:13px;">
                        <input type="checkbox" id="susp_confirm" name="confirmed" value="1" required>
                        <label for="susp_confirm" style="cursor:pointer; margin:0;">I confirm this action</label>
                    </div>
                    <button type="submit" class="btn btn-warning btn-sm" style="margin-top:8px;">SUSPENDUSER</button>
                </form>
            </div>

            <!-- UNSUSPENDUSER -->
            <div class="action-cell">
                <h4>Unsuspend</h4>
                <p style="font-size:12px; color:#57606a; margin:0 0 10px;">Restores login access.</p>
                <form method="POST" action="sysop.php">
                    <input type="hidden" name="csrf"   value="<?= htmlspecialchars($csrfToken, ENT_QUOTES) ?>">
                    <input type="hidden" name="action" value="unsuspenduser">
                    <div class="form-row">
                        <label class="form-label" for="unsusp_userid">User ID</label>
                        <input class="form-input" type="text" id="unsusp_userid" name="userid" maxlength="30" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm" style="margin-top:8px;">UNSUSPENDUSER</button>
                </form>
            </div>

            <!-- DELETEUSER -->
            <div class="action-cell">
                <h4>Delete</h4>
                <p style="font-size:12px; color:#82071e; margin:0 0 10px;">
                    Marks account as deleted. Use UNDELETEUSER to recover.
                </p>
                <form method="POST" action="sysop.php">
                    <input type="hidden" name="csrf"   value="<?= htmlspecialchars($csrfToken, ENT_QUOTES) ?>">
                    <input type="hidden" name="action" value="deleteuser">
                    <div class="form-row">
                        <label class="form-label" for="del_userid">User ID</label>
                        <input class="form-input" type="text" id="del_userid" name="userid" maxlength="30" required>
                    </div>
                    <div class="form-row" style="display:flex; gap:8px; align-items:center; font-size:13px;">
                        <input type="checkbox" id="del_confirm" name="confirmed" value="1" required>
                        <label for="del_confirm" style="cursor:pointer; margin:0;">I confirm deletion</label>
                    </div>
                    <button type="submit" class="btn btn-danger btn-sm" style="margin-top:8px;">DELETEUSER</button>
                </form>
            </div>

            <!-- UNDELETEUSER -->
            <div class="action-cell">
                <h4>Undelete</h4>
                <p style="font-size:12px; color:#57606a; margin:0 0 10px;">Recovers a previously deleted account.</p>
                <form method="POST" action="sysop.php">
                    <input type="hidden" name="csrf"   value="<?= htmlspecialchars($csrfToken, ENT_QUOTES) ?>">
                    <input type="hidden" name="action" value="undeleteuser">
                    <div class="form-row">
                        <label class="form-label" for="undel_userid">User ID</label>
                        <input class="form-input" type="text" id="undel_userid" name="userid" maxlength="30" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm" style="margin-top:8px;">UNDELETEUSER</button>
                </form>
            </div>

        </div>
    </div>

    <!-- ==============================================================
         SECTION 6: Update User Field
         ============================================================== -->
    <div class="section-label">6 &mdash; Update User Field</div>
    <div class="action-section">
        <div class="section-head">UPDATEUSERFIELD</div>
        <div style="padding:16px;">
            <p style="font-size:13px; color:#57606a; margin:0 0 12px;">
                Update a specific field in a user's BBS record.
                Format sent: <span class="code">UPDATEUSERFIELD fieldname userid%%newvalue</span>
            </p>
            <form method="POST" action="sysop.php">
                <input type="hidden" name="csrf"   value="<?= htmlspecialchars($csrfToken, ENT_QUOTES) ?>">
                <input type="hidden" name="action" value="updateuserfield">
                <div class="form-inline">
                    <div class="form-row">
                        <label class="form-label" for="upd_fieldname">Field Name</label>
                        <input class="form-input" type="text" id="upd_fieldname" name="fieldname" maxlength="30" placeholder="e.g. city" required>
                    </div>
                    <div class="form-row">
                        <label class="form-label" for="upd_userid">User ID</label>
                        <input class="form-input" type="text" id="upd_userid" name="userid" maxlength="30" required>
                    </div>
                    <div class="form-row">
                        <label class="form-label" for="upd_newvalue">
                            New Value
                            <span class="hint">(can be blank to clear)</span>
                        </label>
                        <input class="form-input" type="text" id="upd_newvalue" name="newvalue" maxlength="128">
                    </div>
                    <div class="form-row" style="min-width:auto; flex:0; align-self:flex-end;">
                        <button type="submit" class="btn btn-primary">UPDATEUSERFIELD</button>
                    </div>
                </div>
            </form>
        </div>
    </div>


<?php else: ?>
    <!-- ==================================================================
         UNAUTHENTICATED VIEW — login form
         ================================================================== -->

    <div class="page-head">
        <h1>Sysop Panel Demo</h1>
        <p>Account management tools. Requires sysop privileges.</p>
    </div>

    <div style="max-width:400px;">

        <div class="box">
            <div class="box-head">Login Required</div>
            <div class="box-body">

                <?php if ($authError !== ''): ?>
                <div class="flash flash-error"><?= htmlspecialchars($authError, ENT_QUOTES) ?></div>
                <?php endif; ?>

                <div class="flash flash-info" style="margin-bottom:16px;">
                    Only accounts with sysop privileges can access this panel.
                </div>

                <form method="POST" action="sysop.php" autocomplete="off"
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
