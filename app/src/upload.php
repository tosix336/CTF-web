<?php
require __DIR__.'/config.php'; require_admin();
$expected = trim(file_get_contents('/run/app/upload.key'));
if (!hash_equals($expected, $_POST['key'] ?? '')) die('bad upload key');
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) die('upload failed');
$name = basename($_FILES['file']['name']);
if (!preg_match('/\.(jpg|png|phtml)$/i', $name)) die('unsupported artifact');
$target = __DIR__.'/uploads/'.$name;
move_uploaded_file($_FILES['file']['tmp_name'], $target);
if (!file_exists(__DIR__.'/uploads/.vault_flag')) {
  $platform_flag = getenv('GZCTF_FLAG');
  $flag = ($platform_flag !== false && $platform_flag !== '')
    ? $platform_flag
    : 'CTF{vault_'.bin2hex(random_bytes(12)).'}';
  file_put_contents(__DIR__.'/uploads/.vault_flag', $flag);
}
echo 'uploaded: <a href="uploads/'.rawurlencode($name).'">'.htmlspecialchars($name).'</a>';
?>
