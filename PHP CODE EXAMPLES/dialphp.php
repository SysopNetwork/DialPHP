<?php
/**
 * DialPHP Client Library
 *
 * Handles all TCP communication with the DialPHP BBS module.
 *
 * Protocol flow (one command per connection):
 *   1. Connect to BBS on the configured TCP port
 *   2. Read server greeting (terminated by two 0xF5 bytes)
 *   3. Send shared secret + CRLF
 *   4. Read confirmation message ("Secret is good...")
 *   5. Send command with parameters + CRLF
 *   6. Read response ("Answer is : [RESULT]")
 *   7. Connection is closed by the BBS after responding
 *
 * Each call to query() opens a fresh connection. This is by design —
 * the BBS terminates the socket after every command response.
 *
 * Copyright (c) 2026 Mark Laudenbach
 * Sysop Network
 * Licensed under the MIT License
 */


/**
 * Thrown when a connection, authentication, or protocol error occurs.
 */
class DialPHPException extends RuntimeException {}


/**
 * DialPHP — single-command TCP client for the DialPHP BBS module.
 *
 * Basic usage:
 *   $bbs    = new DialPHP('bbsgames.com', 3425, 'your-secret', 5);
 *   $exists = $bbs->query('USERIDEXISTS', ['Tim']);       // "YES" or "NO"
 *   $auth   = $bbs->query('AUTHUSER', ['Tim', 'pass']);   // "Password is correct"
 *   $class  = $bbs->query('CURRENTCLASS', ['Tim']);       // "USER", "SYSOP", etc.
 */
class DialPHP
{
    /**
     * Two ASCII-245 bytes appended to every server message.
     * Read until this sequence to detect end-of-message.
     */
    const TERMINATOR = "\xF5\xF5";

    /**
     * Double-percent separator used in compound parameters.
     *
     * Commands that use this separator:
     *   AUTHUSER userid%%password
     *   UPDATEUSERFIELD fieldname userid%%newvalue
     *
     * All other commands use space-separated parameters.
     */
    const SEPARATOR = '%%';

    /** @var string BBS server hostname or IP */
    private string $host;

    /** @var int BBS TCP port */
    private int $port;

    /** @var string Shared secret phrase */
    private string $secret;

    /** @var int Socket timeout in seconds */
    private int $timeout;

    /** @var resource|null Active TCP socket */
    private $socket = null;

    /**
     * @param string $host    BBS hostname or IP address
     * @param int    $port    TCP port (typically 3425)
     * @param string $secret  Shared secret set in the DialPHP module options
     * @param int    $timeout Connection and read timeout in seconds
     */
    public function __construct(string $host, int $port, string $secret, int $timeout = 5)
    {
        $this->host    = $host;
        $this->port    = $port;
        $this->secret  = $secret;
        $this->timeout = $timeout;
    }

    /**
     * Execute one BBS command and return the result string.
     *
     * Opens a TCP connection, authenticates, sends the command, reads the
     * response, closes the connection, and returns the result value.
     *
     * @param  string   $command  A supported DialPHP command verb (e.g. USERIDEXISTS)
     * @param  string[] $params   Command parameters (order and count vary by command)
     * @return string             Result value extracted from "Answer is : [RESULT]"
     * @throws DialPHPException   On any connection, auth, or protocol failure
     */
    public function query(string $command, array $params = []): string
    {
        $this->connect();

        try {
            // Read and discard the server greeting
            $this->readUntilTerminator();

            // Authenticate with the shared secret
            $this->writeLine($this->secret);

            // Confirm the secret was accepted
            $confirm = $this->readUntilTerminator();
            if (stripos($confirm, 'Secret is good') === false) {
                throw new DialPHPException('Authentication failed — check your secret');
            }

            // Send the command — sanitize params to block separator injection
            $sanitized = array_map([$this, 'sanitizeParam'], $params);
            $this->writeLine($this->buildCommand($command, $sanitized));

            // Read and parse the response
            $response = $this->readUntilTerminator();
            $prefix   = 'Answer is : ';

            if (strncmp($response, $prefix, strlen($prefix)) !== 0) {
                throw new DialPHPException('Unexpected response format from BBS server');
            }

            return substr($response, strlen($prefix));

        } finally {
            // Always close the socket regardless of success or exception
            $this->disconnect();
        }
    }

    /**
     * Reject any parameter containing the wire-level separator.
     * A %% inside a parameter would shift token boundaries on the BBS side.
     */
    private function sanitizeParam(string $param): string
    {
        if (strpos($param, self::SEPARATOR) !== false) {
            throw new DialPHPException('Parameter may not contain the %% separator');
        }
        return $param;
    }

    /**
     * Format the command string with its parameters.
     *
     * Parameter format rules:
     *   AUTHUSER         → AUTHUSER userid%%password
     *   UPDATEUSERFIELD  → UPDATEUSERFIELD fieldname userid%%newvalue
     *   Everything else  → COMMAND param1 param2 ...
     */
    private function buildCommand(string $command, array $params): string
    {
        if (empty($params)) {
            return $command;
        }

        switch (strtoupper($command)) {
            case 'AUTHUSER':
                // Join all params with the separator: userid%%password
                return $command . ' ' . implode(self::SEPARATOR, $params);

            case 'UPDATEUSERFIELD':
                // First param is field name (space), remaining two are userid%%value
                $field  = $params[0] ?? '';
                $userid = $params[1] ?? '';
                $value  = $params[2] ?? '';
                return $command . ' ' . $field . ' ' . $userid . self::SEPARATOR . $value;

            default:
                // Standard space-separated parameters
                return $command . ' ' . implode(' ', $params);
        }
    }

    /**
     * Open a blocking TCP socket to the BBS server.
     */
    private function connect(): void
    {
        $errno  = 0;
        $errstr = '';

        $this->socket = @fsockopen($this->host, $this->port, $errno, $errstr, $this->timeout);

        if ($this->socket === false) {
            throw new DialPHPException(
                "Cannot connect to {$this->host}:{$this->port} — {$errstr} (error {$errno})"
            );
        }

        stream_set_blocking($this->socket, true);
        stream_set_timeout($this->socket, $this->timeout);
    }

    /**
     * Close the TCP socket if it is still open.
     */
    private function disconnect(): void
    {
        if (is_resource($this->socket)) {
            fclose($this->socket);
        }
        $this->socket = null;
    }

    /**
     * Send one line of text to the BBS, appending CRLF.
     */
    private function writeLine(string $text): void
    {
        if (!is_resource($this->socket)) {
            throw new DialPHPException('Cannot write: socket is not open');
        }

        if (strpos($text, "\r") !== false || strpos($text, "\n") !== false) {
            throw new DialPHPException('Parameter contains illegal line-break characters');
        }

        if (@fwrite($this->socket, $text . "\r\n") === false) {
            throw new DialPHPException('Failed to write to socket');
        }

        @fflush($this->socket);
    }

    /**
     * Read bytes from the socket until the two-byte TERMINATOR sequence appears.
     *
     * Returns the accumulated data without the terminator bytes.
     *
     * @throws DialPHPException on timeout, premature connection close, or oversized response
     */
    private function readUntilTerminator(): string
    {
        if (!is_resource($this->socket)) {
            throw new DialPHPException('Cannot read: socket is not open');
        }

        $buffer  = '';
        $term    = self::TERMINATOR;
        $termLen = strlen($term);
        $start   = time();

        while (true) {
            // Enforce overall read timeout (in case stream timeout doesn't trigger)
            if ((time() - $start) > $this->timeout) {
                throw new DialPHPException("Read timed out after {$this->timeout} seconds");
            }

            $byte = fread($this->socket, 1);

            if ($byte === false || $byte === '') {
                $meta = stream_get_meta_data($this->socket);
                if ($meta['timed_out']) {
                    throw new DialPHPException('Stream read timed out');
                }
                if (feof($this->socket)) {
                    throw new DialPHPException('Server closed the connection before sending a complete response');
                }
                throw new DialPHPException('Socket read returned empty without EOF');
            }

            $buffer .= $byte;

            // Check whether the last two bytes are the terminator
            if (strlen($buffer) >= $termLen
                && substr($buffer, -$termLen) === $term) {
                return substr($buffer, 0, -$termLen);
            }

            // 64 KB cap — BBS responses are never this large in practice
            if (strlen($buffer) > 65536) {
                throw new DialPHPException('Response exceeded maximum buffer size (64 KB)');
            }
        }
    }

    /**
     * Ensure the socket is closed when this object is destroyed.
     */
    public function __destruct()
    {
        $this->disconnect();
    }
}
