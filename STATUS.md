# Project status

Verified on 24 September 2026. **MVP functional; project remains in development.** Independent educational simulation; no affiliation with Lemonway, no real payments or banking data.

## Stable public links

- GitHub: https://github.com/tarekfrk79-svg/merchant-payments-api-php
- Railway: https://merchantpay-production.up.railway.app
- Health: https://merchantpay-production.up.railway.app/health

## Completed and verified

- PHP 8.4 / Symfony 7.4, PHP-FPM + Nginx image deployed on Railway.
- Separate Railway PostgreSQL 18 service and persistent volume; Doctrine migration executed successfully.
- Public landing page and JSON liveness: HTTP 200.
- Merchant create/read; payment create/read/list; integer cents; DTO validation.
- Required X-API-Key for writes; required Idempotency-Key for payments.
- Same key/content: 200 with the existing payment. Changed content: 409.
- Unique database index, request fingerprint, repository/service architecture and injected fake processor.
- Validation errors, missing merchant, malformed JSON and failed simulations checked over public HTTP.
- Eight concurrent identical live requests: **one 201, seven 200, one payment UUID**, with no extra database record in the merchant list.
- Two-minute `scripts/demo.sh` executed successfully on Railway.
- README, OpenAPI, Postman, curl examples, architecture and French presentation included.
- Repository checked: no real .env/.env.local/auth.json or application secrets tracked.

## Quality evidence

- Local PHPUnit on PHP 8.4 / SQLite: **18 tests, 46 assertions, passing**.
- GitHub Actions on PostgreSQL 17: migration, schema validation, PHPUnit and PHPStan all passed for the MVP commit (`55274d6`). [Verified run](https://github.com/tarekfrk79-svg/merchant-payments-api-php/actions/runs/35973806396).
- PHPStan level 6: **zero errors**. Portable local PHP reports an optional turbo-extension loading warning; analysis still succeeds. CI passes on standard PHP.
- Composer validate: valid. Composer audit: no known dependency vulnerabilities at verification time.
- Live smoke checks are reproducible with `BASE_URL` and the private `DEMO_API_KEY`: `python3 scripts/smoke.py`.

## Deployment notes

The same GitHub repository and Railway project/service/domain are retained. Railway is connected to GitHub main; the MVP commit was explicitly deployed and verified. Do not assume future pushes are live without checking the Railway deployment commit/status and HTTP routes.

`DATABASE_URL` references Railway PostgreSQL privately, with `?serverVersion=18.0.0&charset=utf8`. Credentials and the demo API key stay in Railway variables. The first Apache image failed at startup; the deployed image uses PHP-FPM/Nginx. `/health` is a liveness check, not a database-readiness promise.

## Remaining beyond the requested MVP

Tenant authentication/authorization, rate limiting, asynchronous outbox/webhooks, broader load tests, observability and operational backup/restore procedures. Read routes are public and the processor is a synchronous fake. Use fictional data only. This prototype is not a real financial service.
