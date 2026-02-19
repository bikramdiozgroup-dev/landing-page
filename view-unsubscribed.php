<?php
// view-unsubscribed.php
// Reads from unsubscribed-emails.txt and displays in table format

$file = __DIR__ . '/unsubscribed-emails.txt';
$rows = [];
$error = null;

if (file_exists($file)) {
    $content = file_get_contents($file);
    if ($content) {
        $lines = array_filter(array_map('trim', explode("\n", $content)));
        foreach ($lines as $line) {
            // Parse new format: "email@example.com | 2025-02-19 18:33:36 | IP: 122.161.240.152 | UA: Mozilla/5.0..."
            if (preg_match('/^(.+?)\s*\|\s*(.+?)\s*\|\s*IP:\s*(.+?)\s*\|\s*UA:\s*(.+)$/', $line, $matches)) {
                $rows[] = [
                    'email' => trim($matches[1]),
                    'timestamp' => trim($matches[2]),
                    'ip' => trim($matches[3]),
                    'source' => 'web',
                    'user_agent' => trim($matches[4])
                ];
            } else if (preg_match('/^(.+?)\s*-\s*(.+?)\s*-\s*IP:\s*(.+)$/', $line, $matches)) {
                // Fallback: old format
                $rows[] = [
                    'email' => trim($matches[1]),
                    'timestamp' => trim($matches[2]),
                    'ip' => trim($matches[3]),
                    'source' => 'web',
                    'user_agent' => 'N/A'
                ];
            } else if (filter_var(trim(explode('|', $line)[0]), FILTER_VALIDATE_EMAIL)) {
                // Just email
                $rows[] = [
                    'email' => trim(explode('|', $line)[0]),
                    'timestamp' => date('Y-m-d H:i:s'),
                    'ip' => 'N/A',
                    'source' => 'web',
                    'user_agent' => 'N/A'
                ];
            }
        }
    }
}

$total = count($rows);
// Reverse to show newest first
$rows = array_reverse($rows);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Unsubscribed Emails | Dioz Group</title>
<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: Arial, Helvetica, sans-serif;
    background: #f5f5f5;
    padding: 30px 15px;
    color: #333;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    background: white;
    border-radius: 8px;
    padding: 30px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

h1 {
    font-size: 28px;
    margin-bottom: 8px;
    color: #111;
}

.subtitle {
    color: #666;
    margin-bottom: 20px;
    font-size: 14px;
}

.stats {
    background: #f0f4ff;
    border-left: 4px solid #2563eb;
    padding: 12px;
    margin-bottom: 20px;
    border-radius: 4px;
}

.error {
    background: #fdecea;
    border: 1px solid #f5c6cb;
    padding: 12px;
    margin-bottom: 16px;
    border-radius: 6px;
    color: #a71d2a;
}

.notice {
    background: #fff3cd;
    border: 1px solid #ffeeba;
    padding: 12px;
    margin-bottom: 16px;
    border-radius: 6px;
    color: #856404;
    text-align: center;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
    overflow-x: auto;
}

thead {
    background: #1a1a1a;
    color: white;
}

th {
    padding: 12px;
    text-align: left;
    font-weight: 600;
    font-size: 13px;
}

td {
    padding: 12px;
    border-bottom: 1px solid #eee;
    font-size: 13px;
}

tbody tr:hover {
    background: #f9f9f9;
}

.email {
    color: #2563eb;
    font-weight: 500;
    word-break: break-all;
}

.ip {
    color: #666;
    font-family: monospace;
    font-size: 12px;
}

.timestamp {
    color: #999;
    white-space: nowrap;
    font-size: 12px;
}

.user-agent {
    color: #666;
    font-size: 12px;
    max-width: 200px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.btn {
    display: inline-block;
    background: #2563eb;
    color: white;
    padding: 10px 20px;
    border-radius: 6px;
    text-decoration: none;
    text-align: center;
    border: none;
    cursor: pointer;
    margin-top: 20px;
    margin-right: 10px;
    transition: background 0.3s;
    font-size: 14px;
    font-weight: 600;
}

.btn:hover {
    background: #1e40af;
}

.btn-secondary {
    background: #666;
}

.btn-secondary:hover {
    background: #444;
}

.empty {
    text-align: center;
    padding: 40px;
    color: #999;
}

@media (max-width: 768px) {
    .user-agent {
        max-width: 100px;
    }
    
    th, td {
        padding: 8px;
        font-size: 12px;
    }
}
</style>
</head>
<body>

<div class="container">
    <h1>📧 Unsubscribed Emails</h1>
    <p class="subtitle">Below is a list of all unsubscribed users.</p>

    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="stats">
        Total Unsubscribed: <strong><?= $total ?></strong>
    </div>

    <?php if ($total === 0): ?>
        <div class="notice">
            ✅ No unsubscribes yet.
        </div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th style="width: 25%;">Email</th>
                    <th style="width: 12%;">Source</th>
                    <th style="width: 15%;">IP</th>
                    <th style="width: 28%;">User Agent</th>
                    <th style="width: 20%;">Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                <tr>
                    <td class="email"><?= htmlspecialchars($row['email']) ?></td>
                    <td><?= htmlspecialchars($row['source']) ?></td>
                    <td class="ip"><?= htmlspecialchars($row['ip']) ?></td>
                    <td class="user-agent" title="<?= htmlspecialchars($row['user_agent']) ?>"><?= htmlspecialchars($row['user_agent']) ?></td>
                    <td class="timestamp"><?= htmlspecialchars($row['timestamp']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <a href="/" class="btn">← Back to Home</a>
        <button class="btn btn-secondary" onclick="window.print()">🖨️ Print</button>
    <?php endif; ?>

</div>

</body>
</html>
