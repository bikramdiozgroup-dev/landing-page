<?php
// unsubscribe-handler.php with comprehensive email verification
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

// Load disposable domains from file
function get_disposable_domains(): array {
    static $cache = null;
    
    if ($cache !== null) {
        return $cache;
    }
    
    $file = __DIR__ . '/disposable-email-domains.txt';
    
    if (!file_exists($file)) {
        $cache = [];
        return $cache;
    }
    
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $domains = [];
    
    foreach ($lines as $domain) {
        $domain = strtolower(trim($domain));
        if (!empty($domain) && strpos($domain, '#') !== 0) {
            $domains[$domain] = true;
        }
    }
    
    $cache = $domains;
    return $cache;
}

// 1. Syntax Validator
function validate_email_syntax(string $email): array {
    $email = trim($email);
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['valid' => false, 'message' => 'Invalid email format.', 'check' => 'Syntax Validator'];
    }
    
    if (strlen($email) > 254) {
        return ['valid' => false, 'message' => 'Email is too long (max 254 characters).', 'check' => 'Syntax Validator'];
    }
    
    $local = explode('@', $email)[0];
    if (strlen($local) > 64) {
        return ['valid' => false, 'message' => 'Local part too long (max 64 characters).', 'check' => 'Syntax Validator'];
    }
    
    // Check for consecutive dots or leading/trailing dots
    if (strpos($email, '..') !== false) {
        return ['valid' => false, 'message' => 'Email contains consecutive dots.', 'check' => 'Syntax Validator'];
    }
    
    if ($local[0] === '.' || $local[strlen($local)-1] === '.') {
        return ['valid' => false, 'message' => 'Email local part starts or ends with dot.', 'check' => 'Syntax Validator'];
    }
    
    return ['valid' => true, 'message' => 'Syntax is valid.', 'check' => 'Syntax Validator'];
}

// 2. Disposable Email Checker
function check_disposable_email(string $email): array {
    $parts = explode('@', $email);
    if (count($parts) !== 2) {
        return ['valid' => false, 'message' => 'Invalid email format.', 'check' => 'Disposable Email Checker'];
    }
    
    $domain = strtolower(trim($parts[1]));
    $disposable_domains = get_disposable_domains();
    
    if (isset($disposable_domains[$domain])) {
        return ['valid' => false, 'message' => 'Disposable/temporary email address detected.', 'check' => 'Disposable Email Checker'];
    }
    
    return ['valid' => true, 'message' => 'Not a disposable email.', 'check' => 'Disposable Email Checker'];
}

// 3. DNS Validity Check & MX Record Checker
function check_mx_records(string $email): array {
    $parts = explode('@', $email);
    if (count($parts) !== 2) {
        return ['valid' => false, 'message' => 'Invalid email format.', 'check' => 'DNS Validity Check'];
    }
    
    $domain = strtolower(trim($parts[1]));
    
    // Check if domain has valid DNS records
    if (!checkdnsrr($domain, 'ANY')) {
        return ['valid' => false, 'message' => 'Domain does not have valid DNS records.', 'check' => 'DNS Validity Check'];
    }
    
    // Check for MX records
    $mxhosts = [];
    if (!getmxrr($domain, $mxhosts)) {
        // Some domains may not have explicit MX records but use A records
        if (!checkdnsrr($domain, 'A') && !checkdnsrr($domain, 'AAAA')) {
            return ['valid' => false, 'message' => 'Domain has no valid mail server (MX records).', 'check' => 'MX Record Checker'];
        }
    }
    
    return ['valid' => true, 'message' => 'Domain has valid MX records.', 'check' => 'MX Record Checker'];
}

// 4. Duplicate Email Remover
function check_duplicate_email(string $email): array {
    $file = __DIR__ . '/unsubscribed-emails.txt';
    
    if (file_exists($file)) {
        $content = file_get_contents($file);
        if ($content && stripos($content, $email) !== false) {
            return ['valid' => false, 'message' => 'Email already unsubscribed.', 'check' => 'Duplicate Email Remover'];
        }
    }
    
    return ['valid' => true, 'message' => 'Email is unique.', 'check' => 'Duplicate Email Remover'];
}

// 5. SMTP Validator (Basic SMTP Check)
function validate_smtp(string $email): array {
    $parts = explode('@', $email);
    if (count($parts) !== 2) {
        return ['valid' => false, 'message' => 'Invalid email format.', 'check' => 'SMTP Validator'];
    }
    
    $domain = strtolower(trim($parts[1]));
    
    // Get MX records
    $mxhosts = [];
    if (!getmxrr($domain, $mxhosts)) {
        if (!checkdnsrr($domain, 'A')) {
            return ['valid' => false, 'message' => 'No mail server found for domain.', 'check' => 'SMTP Validator'];
        }
        $mxhosts = [$domain];
    }
    
    // Try to connect to first MX server
    $mx = $mxhosts[0] ?? $domain;
    $socket = @fsockopen($mx, 25, $errno, $errstr, 5);
    
    if ($socket) {
        fclose($socket);
        return ['valid' => true, 'message' => 'SMTP server is reachable.', 'check' => 'SMTP Validator'];
    }
    
    // If port 25 fails, try other common ports
    foreach ([587, 465, 25] as $port) {
        $socket = @fsockopen($mx, $port, $errno, $errstr, 3);
        if ($socket) {
            fclose($socket);
            return ['valid' => true, 'message' => 'Mail server is reachable.', 'check' => 'SMTP Validator'];
        }
    }
    
    return ['valid' => false, 'message' => 'Could not connect to mail server.', 'check' => 'SMTP Validator'];
}

// 6. Provider Detection
function detect_provider(string $email): array {
    $parts = explode('@', $email);
    $domain = strtolower(trim($parts[1]));
    
    $providers = [
        'gmail.com' => 'Gmail',
        'yahoo.com' => 'Yahoo',
        'hotmail.com' => 'Hotmail',
        'outlook.com' => 'Outlook',
        'aol.com' => 'AOL',
        'protonmail.com' => 'ProtonMail',
        'icloud.com' => 'iCloud',
        'mail.com' => 'Mail.com',
    ];
    
    $provider = $providers[$domain] ?? 'Custom Domain';
    
    return ['valid' => true, 'message' => 'Provider: ' . $provider, 'check' => 'Provider Detection'];
}

// Comprehensive Email Validation
function validate_email_comprehensive(string $email): array {
    $checks = [];
    
    // 1. Syntax Validator
    $syntax_check = validate_email_syntax($email);
    $checks[] = $syntax_check;
    if (!$syntax_check['valid']) {
        return ['valid' => false, 'checks' => $checks];
    }
    
    // 2. Disposable Email Checker
    $disposable_check = check_disposable_email($email);
    $checks[] = $disposable_check;
    if (!$disposable_check['valid']) {
        return ['valid' => false, 'checks' => $checks];
    }
    
    // 3. DNS Validity Check & MX Records
    $mx_check = check_mx_records($email);
    $checks[] = $mx_check;
    if (!$mx_check['valid']) {
        return ['valid' => false, 'checks' => $checks];
    }
    
    // 4. Duplicate Check
    $duplicate_check = check_duplicate_email($email);
    $checks[] = $duplicate_check;
    if (!$duplicate_check['valid']) {
        return ['valid' => false, 'checks' => $checks];
    }
    
    // 5. SMTP Validator
    $smtp_check = validate_smtp($email);
    $checks[] = $smtp_check;
    
    // 6. Provider Detection
    $provider_check = detect_provider($email);
    $checks[] = $provider_check;
    
    return ['valid' => true, 'checks' => $checks];
}

// Get email from POST or GET
$email = isset($_REQUEST['email']) ? trim($_REQUEST['email']) : null;

if (!$email) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Email is required.']);
    exit;
}

// Run comprehensive validation
$validation = validate_email_comprehensive($email);

if (!$validation['valid']) {
    http_response_code(400);
    header('Content-Type: application/json');
    
    // Find first failed check
    $failed_message = 'Email validation failed.';
    foreach ($validation['checks'] as $check) {
        if (!$check['valid']) {
            $failed_message = $check['message'];
            break;
        }
    }
    
    echo json_encode(['success' => false, 'message' => $failed_message, 'checks' => $validation['checks']]);
    exit;
}

// File path for storing unsubscribed emails
$file = __DIR__ . '/unsubscribed-emails.txt';

try {
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
