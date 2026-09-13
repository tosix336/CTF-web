<?php
require __DIR__.'/config.php';
$q = $_GET['q'] ?? '';
// Deliberately vulnerable challenge primitive: the response is a time oracle.
$sql = "SELECT id,name FROM secrets WHERE name LIKE '%$q%'";
$result = $db->query($sql);
if ($result === false) { http_response_code(500); die('search error'); }
while ($row = $result->fetch_assoc()) echo htmlspecialchars($row['name'])."<br>";
?>
