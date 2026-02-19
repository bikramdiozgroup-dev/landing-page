<?php
// unsubscribe-handler.php with disposable domain blocking from file
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

// Load disposable domains from file (with caching in memory)
static $disposable_domains_cache = null;

function get_disposable_domains(): array {
    global $disposable_domains_cache;
    
    if ($disposable_domains_cache !== null) {
        return $disposable_domains_cache;
    }
    
    $file = __DIR__ . '/disposable-email-domains.txt';
    
    if (!file_exists($file)) {
        // Fallback list if file doesn't exist
        $domains = [
            'tempmail.com', 'temp-mail.org', 'throwaway.email', '10minutemail.com',
            'mailinator.com', 'maildrop.cc', 'trashmail.com', 'spam4.me'
        ];
        $disposable_domains_cache = array_flip($domains);
        return $disposable_domains_cache;
    }
    
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!$lines) {
        $disposable_domains_cache = [];
        return $disposable_domains_cache;
    }
    
    // Convert to lowercase and create associative array for fast lookup
    $domains = [];
    foreach ($lines as $domain) {
        $domain = strtolower(trim($domain));
        if (!empty($domain) && strpos($domain, '#') !== 0) { // Skip empty lines and comments
            $domains[$domain] = true;
        }
    }
    
    $disposable_domains_cache = $domains;
    return $disposable_domains_cache;
}

function is_disposable_email(string $email): bool {
    // Extract domain from email
    $parts = explode('@', $email);
    if (count($parts) !== 2) {
        return true; // Invalid format
    }
    
    $domain = strtolower(trim($parts[1]));
    $disposable_domains = get_disposable_domains();
    
    // Check against disposable domains list
    return isset($disposable_domains[$domain]);
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
    $role_accounts = [
        'admin', 'info', 'noreply', 'support', 'test', 'abuse', 'webmaster', 
        'postmaster', 'contact', 'sales', 'hello', 'hi', 'hey', 'news', 'hello',
        'feedback', 'inquiry', 'notification', 'alerts'
    ];
    $local = explode('@', $email)[0];
    if (in_array(strtolower($local), $role_accounts, true)) {
        return ['valid' => false, 'message' => 'Role account emails are not allowed.'];
    }
    
    // Check for disposable domains
    if (is_disposable_email($email)) {
        return ['valid' => false, 'message' => 'Temporary/disposable email addresses are not allowed.'];
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
