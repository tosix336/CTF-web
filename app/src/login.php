<?php
require __DIR__.'/config.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $u = $_POST['username'] ?? ''; $p = $_POST['password'] ?? '';
  $stmt = $db->prepare('SELECT username,role FROM users WHERE username=? AND password=?');
  $stmt->bind_param('ss', $u, $p); $stmt->execute(); $row = $stmt->get_result()->fetch_assoc();
  if ($row) { $_SESSION['user']=$row['username']; $_SESSION['role']=$row['role']; header('Location: admin.php'); exit; }
  $error = 'invalid credentials';
}
?>
<!doctype html><title>Staff Login</title><h1>Staff Login</h1>
<?php if (!empty($error)) echo "<p>$error</p>"; ?>
<form method="post"><input name="username"><input name="password" type="password"><button>Login</button></form>
