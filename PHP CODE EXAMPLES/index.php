<?php
/**
 * DialPHP PHP Examples — Index / Landing Page
 *
 * Overview of all available examples plus a complete command reference.
 * No BBS connection is made from this page.
 *
 * Copyright (c) 2026 Mark Laudenbach
 * Sysop Network
 * Licensed under the MIT License
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DialPHP PHP Examples</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<!-- ====================================================================
     Top Navigation
     ==================================================================== -->
<nav class="nav">
    <a class="nav-brand" href="index.php">Dial<span>PHP</span></a>
    <a href="index.php"  class="active">Home</a>
    <a href="login.php">Login Demo</a>
    <a href="stats.php">Statistics Demo</a>
    <a href="sysop.php">Sysop Panel Demo</a>
</nav>

<!-- ====================================================================
     Main content
     ==================================================================== -->
<main class="page">

    <!-- Page heading -->
    <div class="page-head">
        <h1>DialPHP PHP Examples</h1>
        <p>PHP client examples for the DialPHP Major BBS v10 module. Demonstrates TCP authentication, user queries, and account management.</p>
    </div>

    <!-- About box -->
    <div class="box" style="margin-bottom:24px;">
        <div class="box-head">About DialPHP</div>
        <div class="box-body">
            <p style="margin:0 0 10px;">
                <strong>DialPHP</strong> is a TCP/IP authentication and user-management module for
                <strong>The Major BBS v10</strong>. It listens on a configurable port and accepts
                connections from external scripts, allowing PHP web applications to query and modify
                BBS user accounts in real time.
            </p>
            <p style="margin:0 0 10px;">
                Each connection authenticates with a shared secret, issues one command, receives
                a response, then disconnects. The examples below demonstrate all supported commands.
            </p>
            <p style="margin:0; font-size:13px; color:#57606a;">
                Before using these examples, edit <span class="code">config.php</span> with your
                BBS server address and secret. The shared secret must match the value configured
                in the DialPHP module options on the BBS.
            </p>
        </div>
    </div>

    <!-- Example cards -->
    <div class="section-label">Example Pages</div>
    <div class="grid-2" style="margin-bottom:24px;">

        <a href="login.php" class="card-link">
            <div class="card-icon">&#128274;</div>
            <div class="card-title">Login Demo</div>
            <div class="card-desc">
                Demonstrates user authentication using the AUTHUSER command.
                Enter BBS credentials to verify them and display basic account info.
                Public access — no key required.
            </div>
            <div class="card-tag">Public &rarr;</div>
        </a>

        <a href="stats.php" class="card-link">
            <div class="card-icon">&#128202;</div>
            <div class="card-title">System Statistics Demo</div>
            <div class="card-desc">
                Displays live BBS system variables — total accounts, active users,
                call counts, message totals, and credit statistics. Results are
                cached for 60 minutes to reduce BBS load.
            </div>
            <div class="card-tag">Requires sysop privileges &rarr;</div>
        </a>

        <a href="sysop.php" class="card-link">
            <div class="card-icon">&#9881;&#65039;</div>
            <div class="card-title">Sysop Panel Demo</div>
            <div class="card-desc">
                Full administrative interface covering supported DialPHP
                commands: user lookup, key management, credits and days, class
                switching, account actions, and field updates.
            </div>
            <div class="card-tag">Requires sysop privileges &rarr;</div>
        </a>

    </div>

    <!-- Command reference -->
    <div class="section-label">Complete Command Reference</div>
    <div class="box">
        <div class="box-head">All 24 Supported Commands</div>
        <div class="box-body" style="padding:0;">
            <table class="cmd-table">
                <thead>
                    <tr>
                        <th style="width:28%">Command</th>
                        <th style="width:34%">Parameters</th>
                        <th>Returns</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- User verification -->
                    <tr><td colspan="3" style="background:#f6f8fa; font-size:11px; font-weight:700; color:#57606a; text-transform:uppercase; letter-spacing:.04em; padding:6px 12px;">User Verification</td></tr>
                    <tr>
                        <td><span class="code">USERIDEXISTS</span></td>
                        <td><span class="code">userid</span></td>
                        <td><code class="code">YES</code> or <code class="code">NO</code></td>
                    </tr>
                    <tr>
                        <td><span class="code">AUTHUSER</span></td>
                        <td><span class="code">userid%%password</span></td>
                        <td><code class="code">Password is correct</code> / <code class="code">Password is incorrect</code> / <code class="code">No such user</code></td>
                    </tr>
                    <tr>
                        <td><span class="code">HASMASTER</span></td>
                        <td><span class="code">userid</span></td>
                        <td><code class="code">YES</code> or <code class="code">NO</code></td>
                    </tr>
                    <tr>
                        <td><span class="code">ISSUSPENDED</span></td>
                        <td><span class="code">userid</span></td>
                        <td><code class="code">YES</code> or <code class="code">NO</code></td>
                    </tr>
                    <tr>
                        <td><span class="code">USERONLINE</span></td>
                        <td><span class="code">userid</span></td>
                        <td><code class="code">YES</code> or <code class="code">NO</code></td>
                    </tr>

                    <!-- User information -->
                    <tr><td colspan="3" style="background:#f6f8fa; font-size:11px; font-weight:700; color:#57606a; text-transform:uppercase; letter-spacing:.04em; padding:6px 12px;">User Information</td></tr>
                    <tr>
                        <td><span class="code">PRIMARYCLASS</span></td>
                        <td><span class="code">userid</span></td>
                        <td>Primary class name string</td>
                    </tr>
                    <tr>
                        <td><span class="code">CURRENTCLASS</span></td>
                        <td><span class="code">userid</span></td>
                        <td>Current class name string</td>
                    </tr>
                    <tr>
                        <td><span class="code">NUMBEROFCREDITS</span></td>
                        <td><span class="code">userid</span></td>
                        <td>Credit balance as integer string</td>
                    </tr>
                    <tr>
                        <td><span class="code">NUMBEROFDAYS</span></td>
                        <td><span class="code">userid</span></td>
                        <td>Days remaining (0 for non-expiring classes)</td>
                    </tr>
                    <tr>
                        <td><span class="code">LASTLOGIN</span></td>
                        <td><span class="code">userid</span></td>
                        <td>Date in MM/DD/YYYY format</td>
                    </tr>
                    <tr>
                        <td><span class="code">CREATIONDATE</span></td>
                        <td><span class="code">userid</span></td>
                        <td>Date in MM/DD/YYYY format</td>
                    </tr>

                    <!-- Key management -->
                    <tr><td colspan="3" style="background:#f6f8fa; font-size:11px; font-weight:700; color:#57606a; text-transform:uppercase; letter-spacing:.04em; padding:6px 12px;">Key Management</td></tr>
                    <tr>
                        <td><span class="code">HASKEY</span></td>
                        <td><span class="code">keyname userid</span></td>
                        <td><code class="code">YES</code> or <code class="code">NO</code></td>
                    </tr>
                    <tr>
                        <td><span class="code">GIVEKEY</span></td>
                        <td><span class="code">keyname userid</span></td>
                        <td><code class="code">Key given</code></td>
                    </tr>
                    <tr>
                        <td><span class="code">TAKEKEY</span></td>
                        <td><span class="code">keyname userid</span></td>
                        <td><code class="code">Key taken</code></td>
                    </tr>

                    <!-- Credits & Days -->
                    <tr><td colspan="3" style="background:#f6f8fa; font-size:11px; font-weight:700; color:#57606a; text-transform:uppercase; letter-spacing:.04em; padding:6px 12px;">Credits &amp; Days</td></tr>
                    <tr>
                        <td><span class="code">GIVECREDITS</span></td>
                        <td><span class="code">amount userid</span></td>
                        <td><code class="code">Ok</code> &mdash; use negative amount to subtract</td>
                    </tr>
                    <tr>
                        <td><span class="code">GIVEDAYS</span></td>
                        <td><span class="code">days userid</span></td>
                        <td><code class="code">Ok</code> &mdash; use negative days to subtract; only affects expiring classes</td>
                    </tr>

                    <!-- Class management -->
                    <tr><td colspan="3" style="background:#f6f8fa; font-size:11px; font-weight:700; color:#57606a; text-transform:uppercase; letter-spacing:.04em; padding:6px 12px;">Class Management</td></tr>
                    <tr>
                        <td><span class="code">SWITCHCLASS</span></td>
                        <td><span class="code">classname userid</span></td>
                        <td><code class="code">Ok</code> &mdash; user must already be a member of the target class</td>
                    </tr>

                    <!-- Account actions -->
                    <tr><td colspan="3" style="background:#f6f8fa; font-size:11px; font-weight:700; color:#57606a; text-transform:uppercase; letter-spacing:.04em; padding:6px 12px;">Account Actions</td></tr>
                    <tr>
                        <td><span class="code">SUSPENDUSER</span></td>
                        <td><span class="code">userid</span></td>
                        <td><code class="code">Ok</code></td>
                    </tr>
                    <tr>
                        <td><span class="code">UNSUSPENDUSER</span></td>
                        <td><span class="code">userid</span></td>
                        <td><code class="code">Ok</code></td>
                    </tr>
                    <tr>
                        <td><span class="code">DELETEUSER</span></td>
                        <td><span class="code">userid</span></td>
                        <td><code class="code">Ok</code></td>
                    </tr>
                    <tr>
                        <td><span class="code">UNDELETEUSER</span></td>
                        <td><span class="code">userid</span></td>
                        <td><code class="code">Ok</code></td>
                    </tr>

                    <!-- Advanced -->
                    <tr><td colspan="3" style="background:#f6f8fa; font-size:11px; font-weight:700; color:#57606a; text-transform:uppercase; letter-spacing:.04em; padding:6px 12px;">Advanced</td></tr>
                    <tr>
                        <td><span class="code">UPDATEUSERFIELD</span></td>
                        <td><span class="code">fieldname userid%%newvalue</span></td>
                        <td><code class="code">Ok</code> &mdash; updates a specific field in the user record</td>
                    </tr>
                    <tr>
                        <td><span class="code">AUDITMESSAGE</span></td>
                        <td><span class="code">message text</span></td>
                        <td><code class="code">Ok</code> &mdash; posts a message to the BBS audit trail</td>
                    </tr>
                    <tr>
                        <td><span class="code">SYSTEMVARIABLE</span></td>
                        <td><span class="code">varnum</span></td>
                        <td>Variable value &mdash; see variable index below</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- System variable reference -->
    <div class="box">
        <div class="box-head">SYSTEMVARIABLE — Variable Index</div>
        <div class="box-body" style="padding:0;">
            <table class="cmd-table">
                <thead>
                    <tr>
                        <th style="width:120px">Variable #</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td class="mono">1</td><td>Total downloads</td></tr>
                    <tr><td class="mono">2</td><td>Total uploads</td></tr>
                    <tr><td class="mono">3</td><td>Total messages posted</td></tr>
                    <tr><td class="mono">5</td><td>Total user accounts</td></tr>
                    <tr><td class="mono">6</td><td>Female accounts</td></tr>
                    <tr><td class="mono">7</td><td>Male accounts</td></tr>
                    <tr><td class="mono">10</td><td>Paid credits posted</td></tr>
                    <tr><td class="mono">11</td><td>Free credits posted</td></tr>
                    <tr><td class="mono">12</td><td>Total calls to date</td></tr>
                    <tr><td class="mono">13</td><td>Users currently online</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Protocol notes -->
    <div class="box">
        <div class="box-head">Protocol Notes</div>
        <div class="box-body" style="font-size:13px; color:#1f2328; line-height:1.7;">
            <p><strong>One command per connection.</strong> The BBS closes the TCP connection after every response. Each call to <span class="code">DialPHP::query()</span> opens a fresh connection.</p>
            <p style="margin-top:8px;"><strong>%% separator.</strong> <span class="code">AUTHUSER</span> and <span class="code">UPDATEUSERFIELD</span> use <span class="code">%%</span> (double percent) to separate compound parameters rather than spaces, because userids, passwords, and field values may contain spaces.</p>
            <p style="margin-top:8px;"><strong>Case insensitive.</strong> Command verbs and user IDs are case-insensitive. Passwords for AUTHUSER are case-sensitive.</p>
            <p style="margin-top:8px;"><strong>Message terminator.</strong> The BBS appends two ASCII 245 bytes (0xF5 0xF5) to every outbound message to mark end-of-transmission.</p>
        </div>
    </div>

</main>

<!-- ====================================================================
     Footer
     ==================================================================== -->
<footer class="footer">
    DialPHP PHP Examples &mdash; Copyright &copy; 2026 Mark Laudenbach &bull; Sysop Network &bull;
    <a href="https://github.com/sysopnetwork/dialphp" target="_blank">GitHub</a>
</footer>

</body>
</html>
