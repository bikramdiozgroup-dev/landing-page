<?php
// unsubscribe-handler.php with email validation and disposable domain blocking
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

// Common disposable email domains (subset - you can expand this list)
$disposable_domains = [
    'tempmail.com', 'temp-mail.org', 'throwaway.email', '10minutemail.com',
    'mailinator.com', 'maildrop.cc', 'trashmail.com', 'spam4.me',
    'yopmail.com', 'temp.email', 'fakeinbox.com', 'pokemail.net',
    'tempmail.us', 'sharklasers.com', 'throwawaymail.com', 'mailnesia.com',
    'maildrop.cc', 'tempmail.de', 'temp-email.org', 'fake-mail.com',
    'grr.la', 'mailcatch.com', 'minutemail.com', '15minutemail.com',
    'dispostable.com', 'temp.email', 'tempmail.ninja', 'tempmail.eu',
    'temp-mail.io', 'tempmail.pro', 'maildump.io', 'temp.email',
    'protonmailrmez3lotccipshtkleegetolb5c6h2f4c4p7f55z7pd.onion', 'trash-mail.com'
];

function is_disposable_email(string $email): bool {
    global $disposable_domains;
    
    // Extract domain from email
    $parts = explode('@', $email);
    if (count($parts) !== 2) {
        return true; // Invalid format
    }
    
    $domain = strtolower(trim($parts[1]));
    
    // Check against disposable domains list
    return in_array($domain, $disposable_domains, true);
}

function is_valid_email(string $email): array {
    $email = trim($email);
    
    // Check basic format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['valid' => false, 'message' => 'Invalid email format.'];
    }
    
    // Check length (RFC 5321)
    if (strlen($email) > 254) {
        return ['valid' => false, 'message' => 'Email is too long.'];
    }
    
    // Check for role accounts (admin@, info@, etc.)
    $role_accounts = ['admin', 'info', 'noreply', 'support', 'test', 'abuse', 'webmaster', 'postmaster'];
    $local = explode('@', $email)[0];
    if (in_array(strtolower($local), $role_accounts, true)) {
        return ['valid' => false, 'message' => 'Role account emails are not allowed.'];
    }
    
    // Check for disposable domains
    if (is_disposable_email($email)) {
        return ['valid' => false, 'message' => 'Disposable email addresses are not allowed.'];
    }
    
    return ['valid' => true, 'message' => 'Valid email.'];
}

// Get email from POST or GET
$email = isset($_REQUEST['email']) ? trim($_REQUEST['email']) : null;

if (!$email) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Email is required.']);
    exit;
}

// Validate email
$validation = is_valid_email($email);
if (!$validation['valid']) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $validation['message']]);
    exit;
}

// File path for storing unsubscribed emails
$file = __DIR__ . '/unsubscribed-emails.txt';

try {
    // Check if email is already unsubscribed
    if (file_exists($file)) {
        $content = file_get_contents($file);
        if ($content && stripos($content, $email) !== false) {
            // Email already unsubscribed
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
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Server error. Please try again later.']);
    exit;
}
?>
