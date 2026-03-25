<?php
// One-time DB dumper. Writes SQL to wp-content/uploads/loungenie_db_dump.sql
// Attempts to use mysqldump; falls back to simple PHP export (best-effort).
// REMOVE THIS FILE after use.

// Config override via query params (for safety only if provided)
$db_name = getenv('DB_NAME') ?: (isset($_GET['db']) ? $_GET['db'] : 'pools425_wp872');
$db_user = getenv('DB_USER') ?: (isset($_GET['user']) ? $_GET['user'] : 'pools425_wp872');
$db_pass = getenv('DB_PASSWORD') ?: (isset($_GET['pass']) ? $_GET['pass'] : 'p7SFK)8X@3');
$db_host = getenv('DB_HOST') ?: (isset($_GET['host']) ? $_GET['host'] : 'localhost');

$filename = __DIR__ . '/wp-content/uploads/loungenie_db_dump_' . date('Ymd_His') . '.sql';
$urlpath = dirname($_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) . '/wp-content/uploads/' . basename($filename);

// Ensure uploads dir exists
$uploads = __DIR__ . '/wp-content/uploads';
if (!is_dir($uploads)) {
    mkdir($uploads, 0755, true);
}

// Try mysqldump
$cmd = "mysqldump -h{$db_host} -u{$db_user} -p'{$db_pass}' {$db_name} > " . escapeshellarg($filename);
exec($cmd, $out, $rc);
if ($rc === 0 && file_exists($filename)) {
    echo json_encode(['status' => 'ok', 'method' => 'mysqldump', 'file' => basename($filename), 'path' => $filename]);
    exit;
}

// Fallback: basic PHP export using mysqli (structure + data). Might be incomplete on large DBs.
$mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($mysqli->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'DB connect failed: ' . $mysqli->connect_error]);
    exit;
}
$tables = array();
$res = $mysqli->query('SHOW TABLES');
while ($row = $res->fetch_array()) {
    $tables[] = $row[0];
}
$out = "-- PHP fallback DB dump\n-- db: {$db_name}\n\n";
foreach ($tables as $table) {
    $res2 = $mysqli->query("SHOW CREATE TABLE `{$table}`");
    $r2 = $res2->fetch_assoc();
    $out .= "-- Table structure for {$table}\nDROP TABLE IF EXISTS `{$table}`;\n" . $r2['Create Table'] . ";\n\n";
    $rows = $mysqli->query("SELECT * FROM `{$table}`");
    $num = $rows->num_rows;
    if ($num > 0) {
        $out .= "-- Dumping data for {$table} ({$num} rows)\n";
        while ($r = $rows->fetch_assoc()) {
            $cols = array_map(function ($c) {
                return "`" . $c . "`";
            }, array_keys($r));
            $vals = array_map(function ($v) use ($mysqli) {
                return isset($v) ? "'" . $mysqli->real_escape_string($v) . "'" : 'NULL';
            }, array_values($r));
            $out .= "INSERT INTO `{$table}` (" . implode(',', $cols) . ") VALUES (" . implode(',', $vals) . ");\n";
        }
        $out .= "\n";
    }
}
file_put_contents($filename, $out);
if (file_exists($filename)) {
    echo json_encode(['status' => 'ok', 'method' => 'php_fallback', 'file' => basename($filename), 'path' => $filename]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'failed to write dump']);
}
