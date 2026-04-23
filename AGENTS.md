# HealthTrack AGENT Instructions

## 1. Project Overview

- This repository is for the HealthTrack Patient Wellness Portal Laravel technical assessment.
- The goal is to implement the assessment specification closely, not to invent a broader product.
- Favor senior-level judgment with simple, readable code that is easy to explain in a debrief interview.
- Work one phase at a time. Only build what the current assessment step asks for.

## 2. Technical Stack

- Laravel 11
- PHP 8.2+
- MySQL 8
- Redis
- Laravel Sanctum
- Docker Compose
- Nginx

## 3. Working Style

- Prefer minimal, safe edits over large rewrites.
- Do not change unrelated files.
- Preserve existing progress unless the task explicitly asks for a refactor.
- Match existing project patterns when they already align with the assessment.
- Keep naming, table structure, config keys, and API shapes aligned with the assessment wording.
- Choose clarity over cleverness.

## 4. Architecture Rules

- Keep controllers thin.
- Use Form Requests for POST, PUT, and PATCH validation.
- Put business logic in services, actions, or jobs when the logic is non-trivial.
- Use policies or gates for authorization.
- Keep domain rules centralized and easy to trace.
- Prefer Laravel-native features before adding custom abstractions.
- Avoid unnecessary repositories, complex patterns, or speculative abstractions unless the assessment clearly benefits from them.
- Keep code easy to defend in an interview: every class should have a clear reason to exist.

## 5. Database and Schema Rules

- Follow the assessment schema and naming exactly.
- Extend existing Laravel tables when appropriate rather than replacing framework defaults.
- Do not add extra columns or tables unless the assessment requires them.
- Prefer strings over database enums unless the assessment explicitly requires enums.
- Use nullable columns where async enrichment or delayed external data is expected.
- Add sensible indexes for foreign keys and common query paths.
- Add model relationships that map directly to the schema and remove relationships tied to removed columns.
- Use casts for booleans, arrays/JSON, dates, datetimes, and encrypted fields where appropriate.

## 6. API and Validation Rules

- Follow the assessment API contract closely.
- Validate request input with Form Requests, not inline controller validation, for create and update endpoints.
- Keep validation rules explicit, readable, and consistent with config and schema constraints.
- Use API Resources when response shaping becomes non-trivial.
- Keep error handling predictable and Laravel-native.
- Do not invent extra endpoints, filters, or payload fields beyond the assessment without a clear requirement.

## 7. Queue / Cache / Scheduler Rules

- Use Redis-backed queue and cache where the assessment expects them.
- Keep queued jobs focused, serializable, and retry-safe.
- Use the scheduler only for tasks the assessment actually requires.
- Prefer config-driven TTLs, queue names, rate limits, and thresholds.
- Log async and external integration behavior clearly when relevant.

## 8. Safety Rules for Editing

- Make the smallest change that satisfies the task.
- Do not rewrite working code just to match a personal preference.
- Do not add controllers, routes, jobs, policies, migrations, or services unless the current task explicitly asks for them.
- Do not remove user or prior progress unless the task explicitly requires cleanup.
- If a requested change conflicts with existing progress, refactor only the directly affected area.
- Keep Docker, config, schema, and app code changes scoped to the current phase.

## 9. Validation Expectations

- After making changes, run the most relevant lightweight validation available.
- Prefer targeted validation over expensive broad checks when the task is narrow.
- Typical verification commands:
  - `php -l <file>`
  - `php artisan test`
  - `php artisan migrate --graceful`
  - `php artisan config:clear`
  - `php artisan route:list`
  - `docker compose config`
  - `docker compose up --build`
- In the final response, suggest the exact verification commands that match the work that was done.

## 10. Git / Commit Guidance

- Keep commits focused and easy to review.
- Group related changes by assessment step.
- Avoid mixing schema, API, UI, and infrastructure changes unless the task genuinely requires it.
- Do not rename or move files without a clear benefit tied to the assessment.
- Write commit messages that describe the concrete change, not vague progress.
- Before proposing a commit, ensure the diff is scoped, readable, and defensible.
