# HealthTrack Patient Wellness Portal

## Project overview

HealthTrack is a Laravel 11 technical assessment project for a patient wellness portal. It includes role-based API flows for patients, doctors, and admins, plus async enrichment jobs, scheduled analytics, and Dockerized local development.

## Tech stack

- Laravel 11
- PHP 8.2
- MySQL 8
- Redis
- Laravel Sanctum
- Docker Compose
- Nginx

## Implemented feature summary

- Sanctum token authentication with register, login, and logout endpoints
- Patient APIs for profile management, wellness check-ins, medications, and alerts
- Doctor APIs for assigned patients, patient wellness summaries, check-in history, medications, and unread alerts
- Admin APIs for doctor assignment, API call logs, and queue stats
- External API service layer for country, weather, holiday, and drug info lookups
- Async enrichment jobs for patient profile, weather, holiday checks, mood alerts, and medication enrichment
- Scheduled jobs for missed check-in alerts and weekly analytics reports
- Redis-first caching with resilient fallback for key cache-backed reads
- Postman collection and local environment for manual API verification

## Local setup instructions

1. Copy the environment file:

```bash
cp .env.example .env
```

2. Start the containers:

```bash
docker compose up --build
```

3. In a second terminal, generate the application key:

```bash
docker compose exec app php artisan key:generate
```

4. Run the database migrations and seed demo data:

```bash
docker compose exec app php artisan migrate --seed
```

The API will be available at `http://localhost:8000`.

## Docker startup steps

Main startup command:

```bash
docker compose up --build
```

If you prefer detached mode:

```bash
docker compose up --build -d
```

Included services:

- `app`
- `nginx`
- `mysql`
- `redis`
- `queue-worker`
- `scheduler`

## Database migrate/seed commands

Run initial migrations and seed demo data:

```bash
docker compose exec app php artisan migrate --seed
```

If you want a clean reset:

```bash
docker compose exec app php artisan migrate:fresh --seed
```

## Queue worker command

The Docker setup already includes a `queue-worker` service that runs automatically.

Manual equivalent command:

```bash
docker compose exec app php artisan queue:work redis --queue=wellness,notifications,analytics
```

## Scheduler note / how scheduled jobs are registered

The jobs are scheduled in `routes/console.php` to follow Laravel 11 standerd.

The Docker setup includes a `scheduler` service that runs:

```bash
php artisan schedule:run
```

every 60 seconds in a loop.

Registered scheduled jobs:

- `DailyMissedCheckinAlertJob` at 08:00 AM daily
- `WeeklyAnalyticsReportJob` at 07:00 AM every Monday

Useful scheduler checks:

```bash
docker compose exec app php artisan schedule:list
```

```bash
docker compose exec app php artisan schedule:run
```

## Postman import instructions

Postman assets are in the `/postman` directory:

- `postman/HealthTrack.postman_collection.json`
- `postman/HealthTrack.local.postman_environment.json`

To use them:

1. Open Postman
2. Import both files
3. Select the `HealthTrack Local` environment
4. Start with an auth request to obtain a token

The environment is configured for local Docker access at `http://localhost:8000`.

## Seeded credentials

All seeded users use the same password:

```text
Password@123
```

Users:

- `admin@healthtrack.test` - admin
- `doctor@healthtrack.test` - doctor
- `doctor2@healthtrack.test` - doctor
- `patient1@healthtrack.test` - patient
- `patient2@healthtrack.test` - patient
- `patient3@healthtrack.test` - patient
- `patient4@healthtrack.test` - patient
- `patient5@healthtrack.test` - patient

## Main API groups

- `/api/v1/auth`
  - register, login, logout
- `/api/v1/patient`
  - profile, check-ins, medications, alerts
- `/api/v1/doctor`
  - assigned patients, wellness summary, patient check-ins, patient medications, alerts
- `/api/v1/admin`
  - assign doctor, API logs, queue stats

## Useful verification commands

Show the current API routes:

```bash
docker compose exec app php artisan route:list
```

Show only auth routes:

```bash
docker compose exec app php artisan route:list --path=api/v1/auth
```

Show only patient routes:

```bash
docker compose exec app php artisan route:list --path=api/v1/patient
```

Show only doctor routes:

```bash
docker compose exec app php artisan route:list --path=api/v1/doctor
```

Show only admin routes:

```bash
docker compose exec app php artisan route:list --path=api/v1/admin
```

Watch the queue worker logs:

```bash
docker compose logs -f queue-worker
```

Check scheduler registration:

```bash
docker compose exec app php artisan schedule:list
```

Inspect recent API call logs from the database:

```bash
docker compose exec app php artisan tinker --execute="dump(App\Models\ApiCallLog::latest()->take(10)->get(['api_name','endpoint','response_status','was_cached'])->toArray())"
```

## Notes / assumptions

- Registration is public but always creates a patient user.
- Doctor-patient assignment is intentionally simplified to a single `assigned_doctor_id` on the patient record.
- External API enrichment is asynchronous and designed to fail gracefully without blocking the main patient or auth flows.
- Redis is the primary cache and queue backend in Docker, with graceful cache fallback implemented for key cache-backed reads.
- More detailed implementation trade-offs are documented in `DECISIONS.md`.
