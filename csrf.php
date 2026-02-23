<?php
// csrf.php — CSRF token helpers
// Requires an active session before calling these functions.

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

function csrf_verify(): void {
    $submitted = $_POST['csrf_token'] ?? '';
    $stored    = $_SESSION['csrf_token'] ?? '';
    if (!$stored || !hash_equals($stored, $submitted)) {
        http_response_code(403);
        exit('<p style="color:red;font-family:sans-serif;text-align:center;margin-top:60px">
              Security error: invalid request token.<br>
              <a href="javascript:history.back()">Go back</a> and try again.</p>');
    }
}
