<?php
$db = new mysqli(getenv('DB_HOST'), getenv('DB_USER'), getenv('DB_PASSWORD'), getenv('DB_NAME'));
if ($db->connect_errno) { http_response_code(503); die('database unavailable'); }
$db->set_charset('utf8mb4');
session_start();
function is_admin(): bool { return ($_SESSION['role'] ?? '') === 'admin'; }
function require_admin(): void { if (!is_admin()) { http_response_code(403); die('admin only'); } }
?>
