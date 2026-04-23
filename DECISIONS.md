# HealthTrack Decisions

## 1. Architecture decisions

- I built this assessment on Laravel 11 with Docker Compose, Nginx, PHP-FPM 8.2, MySQL 8, Redis, and Sanctum because that stack matches the assignment closely and is straightforward for local setup and review.
- I kept the API split by role and use case: `auth`, `patient`, `doctor`, and `admin`. This made the routes easier to manage and also made authorization rules clearer.
- I tried to keep controllers thin, used Form Requests for validation, policies/gates for authorization, and small service classes for reusable logic. This made the code easier to follow and easier to explain.
- I placed external integrations behind dedicated services like `CountryService`, `WeatherService`, `HolidayService`, and `DrugInfoService` using Laravel HTTP Client. This kept API calls out of controllers and jobs and centralized config, caching, and logging.
- I used queue jobs for enrichment and analysis work that should not block the main user action, such as patient profile enrichment, weather enrichment, holiday detection, mood alert creation, medication enrichment, missed check-in alerts, and weekly analytics.
- I kept doctor-patient assignment simple with `users.assigned_doctor_id` instead of introducing a separate mapping table. For this assessment, that felt like the most practical choice and was easy to work with across the required flows.
- I used Redis-first caching for external API responses and doctor wellness summaries, with a centralized fallback to database cache so cache failures would not break the app.
- I maintained a Postman collection during development so the implemented endpoints stayed easy to test and review.

## 2. Trade-offs made under time pressure

- I chose the simplest doctor-patient assignment model that satisfies the assessment: one assigned doctor per patient. In a real product, this might need to support multiple doctors, reassignment history, or care teams.
- I kept the analytics practical instead of trying to overbuild them. The wellness summary focuses on the metrics required in the assessment: recent check-ins, rolling mood average, simple weather and holiday correlation, active medications, and unread alert count.
- I used Laravel queues and the scheduler, but kept the operational side lightweight. I did not introduce heavier tooling like Horizon because it would add more setup than value for this assignment.
- Country enrichment is intentionally simple and currently based on country code lookup. It works for this implementation, but it is not a full location normalization approach.
- OpenFDA enrichment stores warnings when data is available and keeps the field `null` when it is not. This keeps medication logging resilient, though it is not a full medication intelligence layer.
- The cache fallback is practical and centralized, but not designed as a deep platform abstraction for every caching case in a large production system.

## 3. Known gaps / what is incomplete or simplified

- This implementation is built to satisfy the assessment well, but I would not call it fully production-ready without further hardening.
- Automated test coverage is lighter than I would prefer. I relied more on iterative manual verification, command-level checks, and Postman validation during development.
- Alert handling currently exists through `wellness_alerts` records and API endpoints. There is no real delivery channel like email, SMS, or push notifications.
- Doctor and admin reporting is intentionally limited to what the assessment requires. There is no advanced filtering, export, or dashboard layer.
- Weekly analytics are stored and updated on schedule, but the reporting model is intentionally simple.
- The resilient cache fallback assumes Laravel cache stores are configured correctly. It is meant as graceful degradation, not as a replacement for proper infrastructure monitoring.

## 4. What I would improve with more time

- Add a stronger feature and integration test suite covering auth, patient flows, doctor flows, admin flows, queue jobs, scheduler behavior, cache bypass, and external API failure scenarios.
- Improve observability around queue retries, failed jobs, fallback events, and external API latency/error behavior.
- Make doctor-patient assignment more flexible if the domain required many-to-many assignment, reassignment history, or shared care ownership.
- Improve normalization of external API responses with stronger mapping and handling around weather, holidays, and drug data.
- Refine summary cache invalidation and potentially move more analytics precomputation into dedicated reporting flows if the system needed to scale further.
- Add a more formal API resource/documentation layer if this were evolving beyond an assessment project.

## 5. How AI/Codex was used

- I used Codex mainly to move faster on scaffolding, repetitive framework work, and iterative implementation across Docker setup, configuration, schema/modeling, API endpoints, jobs, scheduler registration, caching, and Postman assets.
- I did not use the generated code blindly. I reviewed the output, corrected schema and naming mismatches, simplified places where the code was becoming unnecessarily complex, and aligned the implementation back to the assignment requirements.
- AI helped speed up execution, but the final structure, cleanup passes, and trade-off decisions were made deliberately during implementation.