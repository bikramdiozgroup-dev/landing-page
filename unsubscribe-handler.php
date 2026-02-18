<?php
// public_html/unsubscribe-handler.php
declare(strict_types=1);
require __DIR__ . '/db-config.php'; // ensures $pdo

function get_client_ip(): string {
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) return $_SERVER['HTTP_CF_CONNECTING_IP'];
    foreach (['HTTP_CLIENT_IP','HTTP_X_FORWARDED_FOR','REMOTE_ADDR'] as $k) {
        if (!empty($_SERVER[$k])) {
            $ip = $_SERVER[$k];
            if ($k === 'HTTP_X_FORWARDED_FOR') $ip = explode(',', $ip)[0];
            return trim($ip);
        }
    }
    return '0.0.0.0';
}

// Accept either token or email (token preferred)
$token = isset($_REQUEST['t']) ? trim($_REQUEST['t']) : null;
$email = isset($_REQUEST['email']) ? trim($_REQUEST['email']) : null;
$reason = isset($_POST['reason']) ? trim($_POST['reason']) : null;
$source = isset($_REQUEST['list']) ? trim($_REQUEST['list']) : null;

// Basic input validation
if (!$token && !$email) {
    http_response_code(400);
    exit('Invalid unsubscribe request.');
}

try {
    if ($token) {
        // if you have recipients_tokens, map token->email
        $stmt = $pdo->prepare("SELECT email FROM recipients_tokens WHERE token = :token LIMIT 1");
        $stmt->execute([':token' => $token]);
        $row = $stmt->fetch();
        if ($row && !empty($row['email'])) {
            $email = $row['email'];
        } else {
            exit('Invalid or expired link.');
        }
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        exit('Invalid email address.');
    }

    // Insert/Upsert into unsubscribes
    $sql = "INSERT INTO unsubscribes (email, token, reason, source_list, ip, user_agent)
            VALUES (:email, :token, :reason, :source, :ip, :ua)
            ON DUPLICATE KEY UPDATE unsubscribed_at = VALUES(unsubscribed_at), token = VALUES(token)";
    $ins = $pdo->prepare($sql);
    $ins->execute([
        ':email' => $email,
        ':token' => $token ?: null,
        ':reason' => $reason ?: null,
        ':source' => $source ?: null,
        ':ip' => get_client_ip(),
        ':ua' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 1000),
    ]);

    // Optional: also append to text file for logging/backwards compatibility
    // file_put_contents(__DIR__.'/unsubscribed-emails.txt', $email.PHP_EOL, FILE_APPEND|LOCK_EX);

    // send success page or redirect
    header('Location: /unsubscribe-success.html');
    exit;

} catch (Exception $e) {
    error_log('Unsubscribe error: '.$e->getMessage());
    http_response_code(500);
    exit('Server error.');
}
