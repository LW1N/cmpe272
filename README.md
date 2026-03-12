# Pass & Play (php-mysql-demo)

PHP + MySQL demo site (Pass & Play company site). Built by Jenkins, image promoted via the [selfhosted-webapps](https://github.com/LW1N/selfhosted-webapps) GitOps repo.

## Local development

```bash
# PHP lint
find . -name "*.php" -exec php -l {} \;

# Built-in server (no MySQL; /demo.php will error)
php -S localhost:8000 router.php

# Docker build (use linux/amd64 for k3s)
docker build --platform linux/amd64 -t php-mysql-demo:local .
docker run --rm -p 8080:80 php-mysql-demo:local
```

Notes:
- `router.php` is required for clean URLs (`/login`, `/logout`, `/about`, etc.) when using PHP's built-in server.
- Admin credentials are configured in `admin/config.php` (or via `ADMIN_PASSWORD_HASH` environment variable).

## Security

- Session cookie hardening: `HttpOnly`, `SameSite=Lax`, `Secure` on HTTPS.
- CSRF tokens on state-changing forms (`/login`, `/demo.php`).
- Security headers are set in shared layout (`X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, `Content-Security-Policy`).
- Direct access to sensitive files/directories is denied by `.htaccess` (`data/`, `includes/`, `admin/config.php`, and `*.csv|*.json|*.md`).

## CI/CD

- Push to `main` triggers Jenkins (webhook from this repo).
- Jenkins builds the image, pushes `docker.io/lw1n/php-mysql-demo:sha-<shortsha>` and `:latest`, then updates the GitOps repo’s `apps/php-mysql-demo/kustomization.yaml` and pushes. Flux deploys.

## Docs

- [Contacts directory format](docs/contacts.md)