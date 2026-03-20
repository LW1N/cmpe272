# Pass & Play (php-mysql-demo)

PHP + MySQL demo site (Pass & Play company site).
This folder contains local application source and CI pipeline files used to build `docker.io/lw1n/php-mysql-demo`.

Note: in the root GitOps repository, Flux deploys from `apps/php-mysql-demo`.
This `cmpe272/` folder is typically used for local development and validation.

## App features

- Marketing pages for home, about, news, contacts, and products/services.
- Product catalog with 10 products/services sourced from a shared data file.
- Individual product detail pages with image, description, and back link.
- Cookie-based tracking for:
  - last 5 visited product pages
  - top 5 most visited products
- Admin and login flows used by the demo site.

## Local development

```bash
# PHP lint
find . -name "*.php" -not -path "./.git/*" -print0 | xargs -0 -n1 php -l

# Run test suite
php tests/run_tests.php

# Built-in server (admin login needs ADMIN_PASSWORD_HASH; /demo.php also needs DB_* env vars)
export ADMIN_PASSWORD_HASH="$(php -r "echo password_hash('your_admin_password', PASSWORD_DEFAULT);")"
php -S 127.0.0.1:8000 router.php

# Docker build (use linux/amd64 for k3s)
docker build --platform linux/amd64 -t php-mysql-demo:local .
docker run --rm -p 8080:80 -e ADMIN_PASSWORD_HASH="$ADMIN_PASSWORD_HASH" php-mysql-demo:local
```

Notes:
- `router.php` is required for clean URLs (`/about`, `/products`, `/product`, `/recent-products`, `/most-visited-products`, `/login`, `/logout`, etc.) when using PHP's built-in server.
- `demo.php` uses `DB_HOST`, `DB_NAME`, `DB_USER`, and `DB_PASS` environment variables (defaults: `mysql`, `demo`, `demo`, empty password).
- `ADMIN_PASSWORD_HASH` is required for admin login.
- `STANDARD_USERS_JSON` is optional and can define non-admin users as a JSON object of `userid -> password_hash`.
- Product metadata lives in `data/products.php`, and visit tracking helpers live in `includes/product_helpers.php`.
- Product images are stored under `images/products/`.
- `demo.php` now requires login.

## Security

- Session cookie hardening: `HttpOnly`, `SameSite=Lax`, `Secure` on HTTPS.
- CSRF tokens on state-changing forms (`/login`, `/demo.php`).
- Security headers are set in shared layout (`X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, `Content-Security-Policy`).
- Direct access to sensitive files/directories is denied by `.htaccess` (`data/`, `includes/`, `admin/config.php`, and `*.csv|*.json|*.md`).

## CI/CD

- Jenkins runs PHP lint and `php tests/run_tests.php`.
- Jenkins builds the image and pushes `docker.io/lw1n/php-mysql-demo:sha-<shortsha>` and `latest`.
- Jenkins then updates the image tag in the GitOps repo at `apps/php-mysql-demo/kustomization.yaml`.
- Flux reconciles and deploys the new version to the cluster.

## Docs

- [Contacts directory format](docs/contacts.md)
