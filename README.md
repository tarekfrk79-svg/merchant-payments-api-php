# MerchantPay API

[![Quality](https://github.com/tarekfrk79-svg/merchant-payments-api-php/actions/workflows/ci.yml/badge.svg)](https://github.com/tarekfrk79-svg/merchant-payments-api-php/actions/workflows/ci.yml)

**An independent educational API for simulated merchant payments. No affiliation with Lemonway. No real payments and no banking data.**

**Work in progress:** the first MVP implements merchant registration, payment simulation and database-enforced idempotency. See [STATUS.md](STATUS.md) for verified deployment and test results.

- **Demo:** https://merchantpay-production.up.railway.app
- **Repository:** https://github.com/tarekfrk79-svg/merchant-payments-api-php
- **Contract:** [OpenAPI](openapi.yaml) · [Postman](docs/MerchantPay.postman_collection.json)
- **Design:** [Architecture](docs/architecture.md) · [60-second French pitch](docs/presentation-fr.md)

## Implemented

| Method | Route | Behavior |
| --- | --- | --- |
| GET | `/` | Project presentation and live API status |
| GET | `/health` | Liveness JSON; does not assert database readiness |
| POST | `/api/merchants` | Register a demo merchant (201) |
| GET | `/api/merchants/{id}` | Read a merchant |
| POST | `/api/payments` | Simulate a payment (201), replay (200), conflict (409) |
| GET | `/api/payments/{id}` | Read a payment |
| GET | `/api/merchants/{id}/payments` | List payments; `limit=20&offset=0`, maximum limit 100 |

Write routes require `X-API-Key`; payment creation also requires `Idempotency-Key`. Reads are public for this demonstration: use fictional names and `example.test` emails only. This is not a production payment platform or a tenant-isolated service.

Amounts are **integers in cents**, from 1 to 2,147,483,647. Supported demo currencies: EUR, USD, GBP. An external reference starting with `fail_` produces `FAILED`; other references produce `SUCCEEDED`. `PENDING` is the initial internal state; this synchronous fake processor immediately produces a terminal result.

Idempotency is scoped to `(merchantId, Idempotency-Key)` and enforced by a unique database index. JSON key order and whitespace do not change the request fingerprint. A reused key with different validated content returns 409. Failed simulations are replayable too. Unknown input fields are rejected; do not submit card data.

## Run locally

Requires PHP 8.4+, Composer 2 and PostgreSQL 17+; PHP extensions: pdo_pgsql, mbstring, xml and standard Symfony requirements. SQLite (`pdo_sqlite`) is useful for the fast local tests.

```bash
composer install
cp .env.example .env
# Set DATABASE_URL, APP_SECRET and DEMO_API_KEY in the ignored .env file.
php bin/console doctrine:migrations:migrate --no-interaction
php -S 127.0.0.1:8080 -t public public/index.php
```

Use an empty, dedicated database. Environment variables take precedence over `.env`. Never commit real credentials.

```bash
composer test       # Fast HTTP + persistence tests; defaults to isolated var/test.db
composer analyse    # PHPStan level 6
composer check      # Both
```

To test PostgreSQL, set `DATABASE_URL` to a **dedicated test database**, run migrations with `APP_ENV=test`, then run `composer check`. The test suite clears merchant/payment tables in that database. GitHub Actions does this in an ephemeral PostgreSQL service and validates the Doctrine schema.

## Two-minute demonstration

Set `BASE_URL` and provide the demo key privately (Railway → merchantpay → Variables → `DEMO_API_KEY`). It is never embedded in the page, repository or Postman collection.

```bash
export BASE_URL=https://merchantpay-production.up.railway.app
read -rsp 'Demo API key: ' DEMO_API_KEY; echo
export DEMO_API_KEY
bash scripts/demo.sh
```

The script checks health, creates a fictional merchant, creates a payment, repeats it (same ID), provokes a 409 and reads the payment/list. Requires Bash and Python 3. It leaves a few fictional demo records in the database. See [curl examples](docs/curl.md) for individual calls.

## Deployment

Railway builds the Dockerfile with PHP 8.4-FPM and Nginx. `bin/start` applies migrations before serving; failed migrations stop the deployment. `/health` is the deployment health check. GitHub `main` is the service source. PostgreSQL is a separate Railway service with a persistent volume; `DATABASE_URL` uses its private variable reference with `?serverVersion=18.0.0&charset=utf8` (Railway PostgreSQL 18). Keep the existing project/service/domain for stable CV links.

| Variable | Purpose |
| --- | --- |
| APP_ENV | `prod` on Railway; `dev` locally; `test` for tests |
| APP_DEBUG | Documented as `0`; public entry point always disables debug |
| APP_SECRET | Random Symfony secret |
| DEMO_API_KEY | Secret shared key for demo write access |
| DATABASE_URL | PostgreSQL connection URI (secret) |
| GITHUB_URL | Public repository link on landing page |
| PORT | Railway listener port, default 8080 |

Production errors are JSON without stack traces. API keys, idempotency keys and request hashes are not returned in payment responses. The project does not collect banking data and does not process money.

## Roadmap / deliberate limits

- Tenant-scoped authentication and authorization, rate limits and audit events.
- Asynchronous processing, an outbox, webhooks and retry policies before any real gateway integration.
- Expanded concurrency/load testing, observability and operational backup/restore procedures.
- Stronger deployment gates and immutable image/action pinning.

MIT licensed.
