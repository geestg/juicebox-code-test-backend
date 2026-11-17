# Juicebox Backend Code Test

A Laravel 13 REST API for user accounts, posts, and current weather in Perth.

**Run locally**

Requirements are PHP 8.4.1 or newer with `pdo_mysql` and `pdo_sqlite`, Composer, and Docker Compose with the Docker engine running. Clone the repository and run this command from its root:

```sh
composer juicebox:dev
```

The command installs PHP dependencies, creates `.env` with a generated local database password when needed, waits for MySQL, runs migrations, and starts the API at `http://127.0.0.1:8000`. It preserves an existing `.env`. Use `composer juicebox:setup` to complete setup without starting the HTTP server.

**API reference**

Send `Accept: application/json`. Send `Content-Type: application/json` with JSON bodies. Routes marked Bearer require `Authorization: Bearer <token>`.

| Method | Path | Access | Input and success response |
| --- | --- | --- | --- |
| POST | `/api/register` | Public | `name`, `email`, `password`, `password_confirmation`. Returns `201` and queues a welcome email. |
| POST | `/api/login` | Public | `email`, `password`. Returns `200` with `token` and `token_type`. |
| POST | `/api/logout` | Bearer | Revokes the current token and returns `204`. |
| GET | `/api/users` | Bearer | Optional `page` and `per_page`. Returns `200` with paginated `id` and `name` fields. |
| GET | `/api/users/{id}` | Bearer | Returns `200` with the user's `id` and `name`. |
| GET | `/api/posts` | Bearer | Optional `page` and `per_page`. Returns `200` with posts ordered by newest ID first. |
| GET | `/api/posts/{id}` | Bearer | Returns `200` with the post. |
| POST | `/api/posts` | Bearer | `title` and `body`. Returns `201`. Ownership comes from the authenticated account. |
| PATCH | `/api/posts/{id}` | Owner | Supply `title`, `body`, or both to edit a post. Returns `200`. |
| DELETE | `/api/posts/{id}` | Owner | Deletes the post and returns `204`. |
| GET | `/api/weather` | Public | Returns `200` with current conditions in Perth. |

Paginated responses contain `data`, `current_page`, `per_page`, `last_page`, and `total`. The default page size is 15 and the maximum is 100. Error responses use `401` for invalid credentials or a missing or invalid token, `403` for a forbidden post mutation, `404` for a missing record, `422` for invalid input, and `429` for throttled authentication requests.

**Queue and scheduler**

From separate terminals in the project root:

```sh
php artisan queue:work database
php artisan schedule:work
```

The scheduler queues a weather refresh every hour. After registering a user, use their ID to enqueue a welcome email manually. For example:

```sh
php artisan welcome:send 1
```

The default `MAIL_MAILER=log` writes the email to `storage/logs/laravel.log`. Use `php artisan schedule:list` to inspect the hourly job.

**Perth weather**

The public, non-commercial [Open-Meteo Forecast API](https://open-meteo.com/en/docs) needs no API key or weather environment variables. The response includes the local timestamp in `Australia/Perth`, temperature in Celsius, relative humidity, the WMO weather code, and wind speed in km/h. Data is cached for 15 minutes. If a fresh request fails or returns incomplete data, the API responds with `503` and a generic message.

**Tests**

```sh
php artisan test
```

Feature tests use SQLite in memory. Weather tests fake upstream requests, and email tests fake queue dispatch. Local smoke tests covered MySQL migrations, token revocation, the database worker with the log mailer, and a live Open-Meteo request.
