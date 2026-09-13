#!/bin/sh
set -eu
mkdir -p /run/app
printf '%s\n' 'vault-upload-9f2c' > /run/app/upload.key
chmod 0644 /run/app/upload.key
exec apache2-foreground
