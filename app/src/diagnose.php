<?php
require __DIR__.'/config.php'; require_admin();
$diag_key = $_POST['diag_key'] ?? '';
$stmt = $db->prepare("SELECT value FROM secrets WHERE name='diag_key'");
$stmt->execute(); $expected = $stmt->get_result()->fetch_assoc()['value'] ?? '';
if (!hash_equals($expected, $diag_key)) { http_response_code(403); die('invalid diagnostic key'); }
$host = $_POST['host'] ?? '127.0.0.1';
// Deliberately vulnerable command injection; intended only for this isolated CTF container.
$out = shell_exec("ping -c 1 " . $host . " 2>&1");
header('Content-Type: text/plain'); echo $out;
?>
