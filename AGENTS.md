# Agent instructions

This repository is a Laravel 13 code test. Follow the brief and preserve the documented API contract.

- Prefer Laravel validation, Eloquent relationships, policies, queues, and the HTTP client over custom equivalents.
- Keep code short, readable, and easy to explain. Introduce a class or helper when it has a clear responsibility.
- Derive post ownership from the authenticated user. Use the post policy for writes and keep private user fields out of public response data.
- Keep credentials in `.env`. Never include bearer tokens or local database credentials in commits.
- Cover important errors with feature tests. Fake the weather provider and queue when testing HTTP behavior.
- Run `php artisan test` before committing. Record AI suggestions, decisions, fixes, and verification in `AI_LOG.md`.
- Use concise English documentation without emoji or level-two headings.
