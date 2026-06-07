<?php
/**
 * DialPHP Configuration
 *
 * Edit this file with your BBS server connection details before running examples.
 *
 * SECURITY NOTES:
 *   - The secret grants full ability to query and modify user accounts.
 *   - Never commit this file with a real secret to a public repository.
 *   - Restrict web server access to this file (deny direct HTTP requests).
 *   - Rotate the secret periodically.
 *
 * Copyright (c) 2026 Mark Laudenbach
 * Sysop Network
 * Licensed under the MIT License
 */

return [

    // BBS server hostname or IP address.
    // Use '127.0.0.1' if the BBS is on the same server as the web host.
    'host'    => 'your-bbs-server.com',

    // TCP port the DialPHP module listens on (set in BBS module options; default 3425).
    'port'    => 3425,

    // Shared secret — must exactly match the value configured in the DialPHP module.
    // Keep this confidential; possession of this key allows full user account access.
    'secret'  => 'your-shared-secret',

    // Socket connection and read timeout in seconds.
    // Increase if the BBS server is geographically distant or under heavy load.
    'timeout' => 5,

];
