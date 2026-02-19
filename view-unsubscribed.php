<?php
// view-unsubscribed.php
// Simple version that reads from unsubscribed-emails.txt file
// (No database required)

$file = __DIR__ . '/unsubscribed-emails.txt';
$emails = [];
$error = null;

if (file_exists($file)) {
    $content = file_get_contents($file);
    if ($content) {
        $lines = array_filter(array_map('trim', explode("\n", $content)));
        foreach ($lines as $line) {
            // Parse lines like: "email@example.com - 2025-02-18 10:30:45 - IP: 192.168.1.1"
            $emails[] = $line;
        }
    }
} else {
    $error = "Unsubscribed emails file not found.";
}

$total = count($emails);
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
    font-family: 'Arial', Helvetica, sans-serif;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    padding: 40px 20px;
    color: #111;
}

.container {
    max-width: 900px;
    margin: 0 auto;
    background: white;
    border-radius: 12px;
    padding: 40px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

h1 {
    color: #667eea;
    margin-bottom: 10px;
    font-size: 28px;
}

.subtitle {
    color: #666;
    font-size: 14px;
    margin-bottom: 20px;
}

.stats {
    background: #f0f4ff;
    border-left: 4px solid #667eea;
    padding: 15px;
    border-radius: 6px;
    margin-bottom: 20px;
}

.stats strong {
    color: #667eea;
    font-size: 18px;
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
}

table {
    border-collapse: collapse;
    width: 100%;
    margin-top: 20px;
}

th, td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

th {
    background: #667eea;
    color: white;
    font-weight: 600;
}

tbody tr:hover {
    background: #f9f9f9;
}

.email {
    color: #667eea;
    font-weight: 500;
}

.timestamp {
    color: #999;
    font-size: 13px;
}

.btn {
    display: inline-block;
    background: #667eea;
    color: white;
    padding: 10px 20px;
    border-radius: 6px;
    text-decoration: none;
    text-align: center;
    border: none;
    cursor: pointer;
    margin-top: 20px;
    transition: background 0.3s;
}

.btn:hover {
    background: #764ba2;
}

.empty {
    text-align: center;
    padding: 40px;
    color: #999;
}
</style>
</head>
<body>

<div class="container">
    <h1>📧 Unsubscribed Emails</h1>
    <p class="subtitle">Track all emails that have unsubscribed from your mailing list</p>

    <?php if ($error): ?>
        <div class="error">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="stats">
        <strong><?= $total ?></strong> email(s) have unsubscribed
    </div>

    <?php if ($total === 0): ?>
        <div class="empty">
            <p>✅ No unsubscribes yet. Great start!</p>
        </div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Email & Details</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (array_reverse($emails) as $index => $email): ?>
                <tr>
                    <td><?= $total - $index ?></td>
                    <td>
                        <div class="email"><?= htmlspecialchars($email) ?></div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <a href="/" class="btn">← Back to Home</a>
</div>

</body>
</html>
