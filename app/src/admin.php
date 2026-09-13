<?php require __DIR__.'/config.php'; require_admin(); ?>
<!doctype html><title>Admin Console</title><h1>Admin Console</h1>
<p>Signed in as <?=htmlspecialchars($_SESSION['user'])?></p>
<form action="diagnose.php" method="post"><input name="diag_key" placeholder="diagnostic key"><input name="host" value="127.0.0.1"><button>Run diagnostics</button></form>
<form action="upload.php" method="post" enctype="multipart/form-data">
  <input name="key" placeholder="upload key"><input type="file" name="file"><button>Upload build artifact</button>
</form>
