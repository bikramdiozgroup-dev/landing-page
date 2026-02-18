<?php
// view-unsubscribed.php
// Shows the recent rows from the `unsubscribes` table.
// IMPORTANT: Protect this page (password / IP restrict) — it exposes user emails.

require __DIR__ . '/db-config.php';

$rows = [];
$error = null;

// Prefer PDO ($pdo), otherwise try mysqli using DB_* constants (backwards compatible)
if (isset($pdo) && $pdo instanceof PDO) {
    try {
        $stmt = $pdo->query("SELECT id, email, COALESCE(source_list, '') AS source, ip, user_agent, unsubscribed_at
                             FROM unsubscribes
                             ORDER BY unsubscribed_at DESC
                             LIMIT 500");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $error = 'DB error: ' . $e->getMessage();
    }
} else {
    // fallback to mysqli if DB constants exist
    if (defined('DB_HOST') && defined('DB_USER') && defined('DB_PASS') && defined('DB_NAME')) {
        $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($mysqli->connect_errno) {
            $error = "Database connection failed: " . $mysqli->connect_error;
        } else {
            $res = $mysqli->query("SELECT id, email, COALESCE(source_list,'') AS source, ip, user_agent, unsubscribed_at
                                   FROM unsubscribes
                                   ORDER BY unsubscribed_at DESC
                                   LIMIT 500");
            if ($res) {
                while ($r = $res->fetch_assoc()) {
                    $rows[] = $r;
                }
                $res->free();
            } else {
                $error = "Query failed: " . $mysqli->error;
            }
            $mysqli->close();
        }
    } else {
        $error = "No valid DB connection found. Ensure db-config.php defines \$pdo or DB_* constants.";
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Unsubscribed Emails | Dioz Group</title>
<style>
body{font-family:Arial,Helvetica,sans-serif;background:#f9fafb;margin:0;padding:40px;color:#111;}
.container{max-width:1100px;margin:0 auto;}
table{border-collapse:collapse;width:100%;background:white;border-radius:10px;overflow:hidden;box-shadow:0 4px 12px rgba(0,0,0,0.08);}
th,td{padding:12px 16px;text-align:left;border-bottom:1px solid #eee;vertical-align:top;}
th{background:#111;color:white;}
tr:hover{background:#f1f5f9;}
button{background:#2563eb;color:#fff;border:none;padding:8px 14px;border-radius:6px;cursor:pointer;}
button:hover{background:#1e40af;}
.notice{background:#fff3cd;border:1px solid #ffeeba;padding:12px;margin-bottom:16px;border-radius:6px;}
.error{background:#fdecea;border:1px solid #f5c6cb;padding:12px;margin-bottom:16px;border-radius:6px;color:#a71d2a;}
.small{font-size:0.9rem;color:#666;}
</style>
</head>
<body>
<div class="container">
<h1>📧 Unsubscribed Emails</h1>
<p class="small">Below is a list of recent unsubscribes (last 500). Protect this page — it contains user emails.</p>

<?php if ($error): ?>
  <div class="error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if (count($rows) === 0): ?>
  <div class="notice">No unsubscribes found.</div>
<?php else: ?>
<table>
<tr>
<th style="width:40%;">Email</th>
<th style="width:12%;">Source</th>
<th style="width:12%;">IP</th>
<th style="width:26%;">User Agent</th>
<th style="width:10%;">Date</th>
</tr>
<?php foreach ($rows as $row): ?>
<tr>
<td><?= htmlspecialchars($row['email']) ?></td>
<td><?= htmlspecialchars($row['source'] ?? '') ?></td>
<td><?= htmlspecialchars($row['ip'] ?? '') ?></td>
<td><?= htmlspecialchars(mb_strimwidth($row['user_agent'] ?? '', 0, 80, '...')) ?></td>
<td><?= htmlspecialchars($row['unsubscribed_at'] ?? '') ?></td>
</tr>
<?php endforeach; ?>
</table>

<form method="post" action="export-unsubscribed.php" style="margin-top:20px;">
<button type="submit">⬇️ Export as CSV</button>
</form>
<?php endif; ?>

</div>
</body>
</html>
