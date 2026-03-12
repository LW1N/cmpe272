# Pass & Play (php-mysql-demo)

PHP + MySQL demo site (Pass & Play company site).
This folder contains local application source and CI pipeline files used to build `docker.io/lw1n/php-mysql-demo`.

Note: in the root GitOps repository, Flux deploys from `apps/php-mysql-demo`.
This `cmpe272/` folder is typically used for local development and validation.

## Local development

```bash
# PHP lint
find . -name "*.php" -exec php -l {} \;

# Run test suite
php tests/run_tests.php

# Built-in server (no MySQL by default; /demo.php needs DB_* env vars)
php -S localhost:8000 router.php

# Docker build (use linux/amd64 for k3s)
docker build --platform linux/amd64 -t php-mysql-demo:local .
docker run --rm -p 8080:80 php-mysql-demo:local
```

Notes:
- `router.php` is required for clean URLs (`/login`, `/logout`, `/about`, etc.) when using PHP's built-in server.
- `demo.php` uses `DB_HOST`, `DB_NAME`, `DB_USER`, and `DB_PASS` environment variables (defaults: `mysql`, `demo`, `demo`, empty password).
- Admin credentials are configured in `admin/config.php` (or via `ADMIN_PASSWORD_HASH` environment variable).

## Security

- Session cookie hardening: `HttpOnly`, `SameSite=Lax`, `Secure` on HTTPS.
- CSRF tokens on state-changing forms (`/login`, `/demo.php`).
- Security headers are set in shared layout (`X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, `Content-Security-Policy`).
- Direct access to sensitive files/directories is denied by `.htaccess` (`data/`, `includes/`, `admin/config.php`, and `*.csv|*.json|*.md`).

## CI/CD

- Push to `main` triggers Jenkins (webhook).
- Jenkins builds the image and pushes `docker.io/lw1n/php-mysql-demo:sha-<shortsha>` and `latest`.
- Jenkins then updates the image tag in the GitOps repo at `apps/php-mysql-demo/kustomization.yaml`.
- Flux reconciles and deploys the new version to the cluster.

## Docs

- [Contacts directory format](docs/contacts.md)