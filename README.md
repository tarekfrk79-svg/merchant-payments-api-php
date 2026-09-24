# MerchantPay API

Independent educational project, with no affiliation with Lemonway. A simulated merchant payments API: **no real payments and no banking data**.

**Status: work in progress.** This repository is being developed incrementally.

## Available in the initial milestone

- Symfony application with a professional landing page (`GET /`).
- JSON liveness endpoint (`GET /health`).
- Sanitized JSON errors and an automated health test.
- Environment configuration and a Railway Docker deployment definition.

## Roadmap

Merchant and payment endpoints; PostgreSQL with Doctrine ORM and migrations; idempotency enforced by a database constraint; API key protection; PHPUnit and PHPStan; OpenAPI, Postman and architecture documentation.

## Local setup

Requires PHP 8.4 and Composer. Run `composer install`, then `php -S 127.0.0.1:8080 -t public public/index.php`.
Run `composer test`. Environment variables are documented in `.env.example`; never commit real secrets.

## Deployment

Railway builds the Dockerfile. Public URLs will be recorded after actual verification. See [STATUS.md](STATUS.md).
