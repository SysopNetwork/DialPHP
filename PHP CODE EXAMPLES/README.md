# DialPHP — PHP Client Examples

PHP client library and example pages for the **DialPHP** Major BBS v10 module. These examples demonstrate how to connect a PHP web application to a live BBS server to authenticate users, display account information, query system statistics, and perform administrative operations.

---

## Contents

| File | Description |
|---|---|
| `config.php` | BBS connection settings — **edit this first** |
| `dialphp.php` | DialPHP TCP client class — the only file your app needs to include |
| `ratelimit.php` | IP-based login rate limiter (file-backed, no database required) |
| `index.php` | Landing page with full command reference |
| `login.php` | User authentication demo |
| `stats.php` | BBS system statistics (requires sysop privileges) |
| `sysop.php` | Full admin panel demonstrating all 24 commands (requires sysop privileges) |
| `style.css` | Shared stylesheet for the example pages |

---

## Quick Start

1. **Copy** the contents of this folder to a directory on your web server.
2. **Edit** `config.php` with your BBS server address, port, and shared secret.
3. Open `index.php` in a browser — it will confirm connectivity and display the full command reference.

---

## Configuration

Edit `config.php` before using any of the example pages:

```php
return [

    // BBS server hostname or IP address.
    // Use '127.0.0.1' if the BBS and web server are on the same machine.
    'host'    => 'your-bbs-server.com',

    // TCP port the DialPHP module listens on.
    // Set in the BBS module options; the default is 3425.
    'port'    => 3425,

    // Shared secret — must exactly match the value configured in the DialPHP module.
    // This key grants full read/write access to BBS user accounts. Keep it confidential.
    'secret'  => 'your-shared-secret',

    // Socket connection and read timeout in seconds.
    'timeout' => 5,

];
```

> **Never commit `config.php` with a real secret to a public repository.**

---

## Security Notes

> ⚠️ **Read before deploying to a public-facing server.**

### Shared Secret
The shared secret configured in `config.php` grants **full ability to query and modify every user account on the BBS**. Treat it with the same care as a database root password:
- Use a long, random value (32+ characters).
- Restrict the DialPHP TCP port to your web server's IP address at the BBS server firewall — no public access needed.
- Never log, display, or transmit the secret to the browser.
- Rotate the secret periodically and update both the BBS module options and `config.php`.

### HTTPS
Always serve these pages over **HTTPS**. The session cookies for `stats.php` and `sysop.php` are marked `secure` and will not be sent over plain HTTP. Sysop credentials sent over plain HTTP are vulnerable to interception.

> To test over plain HTTP in development, remove the `'secure' => true` flag from the `session_set_cookie_params()` call in `stats.php` and `sysop.php`. Re-enable it before any public deployment.

### Rate Limiting
All three login forms include IP-based rate limiting backed by `login_attempts.json` in the `data/` subdirectory:
- **5 failed attempts** within a 15-minute window triggers a lockout.
- Locked-out IPs must wait **15 minutes** before trying again.
- The `data/` directory is protected by `.htaccess` (Apache) to prevent direct browser access to the JSON file.

### CSRF Protection
`sysop.php` generates a per-session CSRF token that is validated on every POST. The token is rotated after each successful action to limit the replay window.

### Session Security
- Sessions use `httponly`, `secure`, and `samesite=Strict` cookie flags.
- `session_regenerate_id(true)` is called on every successful login to prevent session fixation.
- Sysop privilege is re-validated against the live BBS on every page load — if privilege is revoked on the BBS, the session is immediately invalidated.

### Input Validation
- All user-supplied input is sanitized with `strip_tags()` and `str_replace()` to remove HTML tags and line-break characters before being passed to the DialPHP client.
- The DialPHP client rejects any parameter containing the `%%` separator and any parameter containing `\r` or `\n`, preventing command injection at the protocol level.

---

## Password Limitations

> ⚠️ **The Major BBS enforces the following password constraints that your application must account for:**

### Maximum Length: 9 Characters
The Major BBS silently truncates passwords to **9 characters** at account creation time. A user who sets their password to `MyPassword123` will actually have the password `MyPasswor` (the first 9 characters). Authentication will succeed with `MyPasswor` and fail with `MyPassword123`.

**Recommendation:** Cap your password input field at 9 characters and display a note to users informing them of this limit.

### Case Insensitive
The BBS stores and compares passwords in a **case-insensitive** manner. `password`, `PASSWORD`, and `Password` are treated as identical credentials.

**Recommendation:** Inform users that their BBS password is not case-sensitive to avoid confusion.

### Example HTML Input
```html
<input type="password" name="password" maxlength="9"
       autocomplete="current-password" required>
<small>BBS passwords are limited to 9 characters and are not case-sensitive.</small>
```

---

## Using the DialPHP Client in Your Own Code

The only file you need to include is `dialphp.php`. Everything else is example scaffolding.

```php
require_once 'dialphp.php';
require_once 'config.php';

$config = require 'config.php';
$bbs    = new DialPHP($config['host'], $config['port'], $config['secret'], $config['timeout']);

// Check if a user exists
$exists = $bbs->query('USERIDEXISTS', ['Tim']);   // "YES" or "NO"

// Authenticate
$auth = $bbs->query('AUTHUSER', ['Tim', 'pass']); // "Password is correct"

// Get the user's current class
$class = $bbs->query('CURRENTCLASS', ['Tim']);    // "USER", "SYSOP", etc.

// Give a user 500 credits
$bbs->query('GIVECREDITS', ['500', 'Tim']);       // "Ok"

// Check for sysop privilege
$isSysop = $bbs->query('HASMASTER', ['Tim']);     // "YES" or "NO"
```

Each call to `query()` opens a fresh TCP connection, authenticates, sends the command, reads the response, and closes the connection. Wrap calls in a `try/catch` to handle `DialPHPException` when the BBS is unreachable.

```php
try {
    $result = $bbs->query('CURRENTCLASS', ['Tim']);
} catch (DialPHPException $e) {
    // BBS is down, timed out, or the secret is wrong
    error_log('DialPHP error: ' . $e->getMessage());
    $result = null;
}
```

---

## Example Pages

### Login Demo (`login.php`)
Demonstrates user authentication using `AUTHUSER`. Enter any BBS credentials to verify them and display a profile summary including class, credits, days remaining, last login, and sysop status.

Access: **Public** — no key required.

### Statistics Demo (`stats.php`)
Displays live BBS system variables using `SYSTEMVARIABLE`. Results are cached to `data/stats_cache.json` for 60 minutes to reduce load on the BBS. If the BBS is unreachable at refresh time, the last cached values are served with a warning.

Access: **Sysop only** — requires `HASMASTER` to return `YES` for the logged-in user.

### Sysop Panel Demo (`sysop.php`)
Full administrative interface covering all 24 DialPHP commands. Includes:
- User profile lookup
- Key management (HASKEY, GIVEKEY, TAKEKEY)
- Credits and days adjustment
- Class switching
- Account suspend, unsuspend, delete, and undelete (with confirmation checkboxes)
- Individual user field updates via UPDATEUSERFIELD

Access: **Sysop only** — requires `HASMASTER` to return `YES`.

---

## Command Reference

See `index.php` for the full interactive command reference, or refer to the [main README](../README.md#command-reference) for a complete table.

---

## File Permissions

The web server user needs **write access** to the `data/` subdirectory (created automatically on first use):

```bash
# On Linux/Apache — adjust www-data to your web server user
chown www-data:www-data data/
chmod 750 data/
```

On Windows with IIS, ensure the IIS application pool identity has Modify permissions on the `data/` directory.

---

<sub>DialASP originally created by DialSoft. DialPHP updated and maintained by Mark Laudenbach &bull; Sysop Network.</sub>
