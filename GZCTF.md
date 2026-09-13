# GZCTF deployment

This challenge must be added to GZCTF as a container challenge. GZCTF does
not execute this repository's `docker-compose.yml` when it starts a challenge;
it pulls one image and starts one container.

## Before creating the challenge

1. Push the repository to GitHub.
2. Wait for the `Publish challenge image` workflow to finish.
3. In the repository's Packages page, set the GHCR package visibility to
   Public.
4. Ask the GZCTF administrator to verify that the GZCTF host can pull:

   ```text
   ghcr.io/tosix336/ctf-web:latest
   ```

The GitHub repository URL is not a Docker image name. Do not enter
`https://github.com/tosix336/CTF-web` in the container image field.

## GZCTF challenge fields

Use these values when creating the challenge:

```text
Type: Dynamic Container
Container image: ghcr.io/tosix336/ctf-web:latest
Exposed port: 80
Network mode: Open
```

Use this dynamic flag template:

```text
CTF{vault_[TEAM_HASH]}
```

The application reads the `GZCTF_FLAG` environment variable injected by
GZCTF and stores it after the intended upload step. Do not hard-code a flag
in the image or in the challenge configuration.

## Why the original deployment failed

The original local setup had two services:

```text
web -> db
```

That works with Docker Compose because Compose creates the `db` service.
GZCTF starts only the challenge image, so the PHP container could not resolve
the hostname `db`. The current image bundles MariaDB for standalone GZCTF
use, while the local Compose file still uses its separate MariaDB service.

## Common mistakes

- Using `ghcr.io/tosix336/my-ctf-challenges:latest`; the current repository is
  `tosix336/CTF-web` and the configured image is `ghcr.io/tosix336/ctf-web:latest`.
- Leaving the GHCR package private.
- Entering port `8088`; `8088` is only the local host port. The container
  listens on port `80`.
- Creating a Static Container challenge instead of a Dynamic Container
  challenge. Dynamic mode is needed for `GZCTF_FLAG`.
- Adding `EXPOSE` or a fixed host port in the challenge Dockerfile. GZCTF
  manages the external port mapping itself.

## Troubleshooting

If the image cannot be pulled, the GZCTF administrator should check the
GZCTF container manager logs and verify the package visibility and exact image
tag.

If the image pulls but the instance immediately stops, check the container
logs for MariaDB initialization errors. The container should keep Apache in
the foreground after MariaDB becomes ready.
