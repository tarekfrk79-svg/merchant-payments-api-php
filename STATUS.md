# Project status

## Verified

- Public GitHub repository: https://github.com/tarekfrk79-svg/merchant-payments-api-php
- Railway project, web service, PostgreSQL and GitHub source connection created.
- Local PHP 8.4: 18 PHPUnit tests / 46 assertions passing against SQLite.
- PHPStan level 6: zero errors. The portable local PHP build reports an optional turbo-extension warning; analysis completes successfully.
- Merchant/payment endpoints, DTO validation, API key, replay/conflict semantics and database uniqueness tested locally.
- README, OpenAPI, Postman, curl/demo script and architecture/presentation documents provided.

## Deployment verification pending

Initial Apache image failed at startup (multiple MPM modules). Switching to PHP-FPM/Nginx. Public domain reserved: https://merchantpay-production.up.railway.app — not yet verified working. PostgreSQL migration, GitHub Actions and live HTTP smoke tests are the next checks. Do not claim the public demo is ready until these checks pass.

## Remaining beyond MVP

Tenant authentication/authorization, rate limiting, async/outbox/webhooks, operational observability and backup procedures. This remains a simulated educational project with public read routes, not a real payment service.
