<?php
/**
 * V1 Admin Export → V2
 *
 * Admin-only. Streams a JSON dump of every table V2's admin importer
 * needs. Read-only against V1.
 */

require 'session_config.php';
require 'dbcon.php';

if (!isset($_SESSION['username'])) {
    $currentUrl = urlencode($_SERVER['REQUEST_URI']);
    header("Location: index.php?redirect=$currentUrl");
    exit;
}
if (($_SESSION['role'] ?? '') !== 'admin') {
    $_SESSION['message'] = 'Admin only.';
    header('Location: home.php');
    exit;
}

$tables = [
    'users', 'iacuc', 'strains', 'settings',
    'cages', 'cage_users', 'cage_iacuc',
    'holding', 'mice', 'breeding', 'litters',
    'files', 'notes', 'tasks', 'maintenance',
    'reminders', 'notifications', 'outbox',
    'activity_log',
];

$payload = [
    'exported_at' => gmdate('c'),
    'source_db'   => $_ENV['DB_DATABASE'] ?? null,
    'tables'      => [],
];

foreach ($tables as $t) {
    $check = $con->prepare("SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
    $check->bind_param("s", $t);
    $check->execute();
    if ($check->get_result()->num_rows === 0) {
        $check->close();
        $payload['tables'][$t] = [];
        continue;
    }
    $check->close();

    $rows = [];
    $res = $con->query("SELECT * FROM `$t`");
    while ($row = $res->fetch_assoc()) $rows[] = $row;
    $res->close();
    $payload['tables'][$t] = $rows;
}

$json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
if ($json === false) {
    http_response_code(500);
    echo 'JSON encode failed: ' . json_last_error_msg();
    exit;
}

$filename = 'v1_export_' . date('Ymd_His') . '.json';

$helper = __DIR__ . '/log_activity.php';
if (file_exists($helper)) {
    require_once $helper;
    if (function_exists('log_activity')) {
        log_activity($con, 'export', 'v1_data', null, "Exported V1 data for V2 migration ($filename)");
    }
}

header('Content-Type: application/json; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($json));
echo $json;
