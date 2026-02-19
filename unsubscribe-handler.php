<?php
// Simple unsubscribe handler - writes to text file with full details
declare(strict_types=1);

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

function get_user_agent(): string {
    return isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 255) : 'Unknown';
}

// Get email from POST or GET
$email = isset($_REQUEST['email']) ? trim($_REQUEST['email']) : null;

// Validate email
if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo '<h2>Error</h2><p>Invalid email address.</p>';
    exit;
}

// File path for storing unsubscribed emails
$file = __DIR__ . '/unsubscribed-emails.txt';

try {
    // Check if email is already unsubscribed
    if (file_exists($file)) {
        $content = file_get_contents($file);
        if (strpos($content, $email) !== false) {
            header('Location: /unsubscribe-success.html');
            exit;
        }
    }

    // Create log entry with email, timestamp, IP, and User Agent
    $log_entry = $email . ' | ' . date('Y-m-d H:i:s') . ' | IP: ' . get_client_ip() . ' | UA: ' . get_user_agent() . PHP_EOL;
    file_put_contents($file, $log_entry, FILE_APPEND | LOCK_EX);

    // Redirect to success page
    header('Location: /unsubscribe-success.html');
    exit;

} catch (Exception $e) {
    error_log('Unsubscribe error: ' . $e->getMessage());
    http_response_code(500);
    echo '<h2>Error</h2><p>Server error: ' . htmlspecialchars($e->getMessage()) . '</p>';
    exit;
}
?>
