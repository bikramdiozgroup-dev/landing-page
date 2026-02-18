<?php
require __DIR__ . '/db-config.php';

$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($mysqli->connect_errno) {
    die("Database connection failed: " . $mysqli->connect_error);
}

header("Content-Type: text/csv");
header("Content-Disposition: attachment; filename=unsubscribed-emails.csv");

$output = fopen("php://output", "w");
fputcsv($output, ['Email', 'Source', 'IP', 'User Agent', 'Date']);

$result = $mysqli->query("SELECT email, source, ip, user_agent, unsubscribed_at FROM unsubscribed_emails ORDER BY unsubscribed_at DESC");
while ($row = $result->fetch_assoc()) {
    fputcsv($output, $row);
}

fclose($output);
$mysqli->close();
exit;
