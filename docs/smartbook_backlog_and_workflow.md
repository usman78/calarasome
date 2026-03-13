# SmartBook Backlog and Delivery Workflow

Source: `smartbook_features.docx` (SRS v2.1 MVP, March 2026)

## 1) Product Backlog (Structured)

### Epic D1: Multi-Provider Management
- F-01 Provider CRUD and Status Management
  - Goal: Manage provider lifecycle with safe deactivation.
  - Critical rules: no hard delete when appointments exist; at least one active provider per clinic.
  - API/UI: `/api/admin/providers` CRUD; Settings -> Providers.
- F-02 Provider-Specific Schedule Management
  - Goal: Per-provider schedule with date effectiveness and treatment scoping.
  - Critical rules: overlapping schedules blocked.
  - API/UI: `/api/admin/providers/:id/schedule`; weekly schedule editor.
- F-03 Provider Blocked Times
  - Goal: Vacation/leave ranges hide slots and block assignment.
  - API/UI: add/remove provider blocked periods.
- F-04 Treatment-Provider Bi-Directional Mapping
  - Goal: Treatment edit updates treatment + provider defaults atomically.
  - Critical rules: atomic sync; never show unmapped provider.

### Epic D2: Patient Booking Flow
- F-05 6-Step Booking Flow
  - Goal: Enforce triage -> treatment -> provider -> slot -> contact/consent/payment -> confirmation.
  - Critical rules: provider qualification, min notice, timezone-safe slot display.
- F-06 Any Available Auto-Assignment
  - Goal: Fair assignment (fewest today + round-robin tie break) at reservation time.
  - Critical rules: assignment lock at reserve step.
- F-07 10-Min Slot Reservation Hold
  - Goal: Prevent race conditions via expiring reservation token.
  - Critical rules: no booking without valid token; cleanup every minute.

### Epic D3: HIPAA-Compliant Communications
- F-08 Consent Collection
  - Goal: Mandatory communication consent and PHI preference at booking.
  - Critical rules: block booking without consent.
- F-09 Standard PHI Email
  - Goal: Send detailed emails only when PHI consented.
- F-10 De-Identified Email
  - Goal: No treatment or sensitive data in private emails; secure link only.
  - Critical rules: token expiry 24h; treatment name never in subject/body.
- F-11 Secure Appointment Detail Page
  - Goal: Token + DOB verification page for private email flows.
  - Critical rules: lockout after 3 failed attempts.

### Epic D4: Payment and Deposit Management
- F-12 Hybrid Deposit Strategy
  - Goal: <= 7 days use manual-capture PaymentIntent; > 7 days use SetupIntent and schedule T-7 auth.
  - Critical rules: medical visits skip deposit flow.
- F-13 T-7 Scheduled Auth Job
  - Goal: Hourly idempotent job converts pending setup to auth hold.
- F-14 Failed T-7 Auth Handling
  - Goal: Immediate patient/admin alert and 48h grace countdown, then auto-cancel if unresolved.
  - Critical rules: no charge during grace period.
- F-15 Cancellation Matrix
  - Goal: Correct void/capture/refund behavior by actor and timing.
  - Critical rules: clinic cancellations always void/cancel schedule.
- F-16 No-Show Capture (Manual Only)
  - Goal: Admin-confirmed no-show captures deposit.
  - Critical rules: never auto-triggered.

### Epic D5: Waitlist Priority and Staggered Notifications
- F-17 Waitlist Scoring
  - Goal: Priority score/tier based on urgency, history, wait time, no-show penalty.
- F-18 Staggered Notifications
  - Goal: tiered rounds with delays and cancellation on claim.
  - Critical rules: no simultaneous blast.

### Epic D6: Insurance Verification Workflow
- F-19 Insurance Data at Booking
  - Goal: capture medical insurance details and classify urgency instantly.
  - Critical rules: critical/high trigger immediate admin alert.
- F-20 Admin Verification Queue
  - Goal: urgency-sorted queue with verify/fail actions.
  - Critical rules: failed status sends immediate patient notification.
- F-21 T-24h Scheduler (Standard Urgency)
  - Goal: daily summary for tomorrow's standard-urgency pending verifications.

### Epic D7: Patient Data Integrity
- F-22 5-Step Matching Algorithm
  - Goal: prevent duplicates with email+DOB first, then fallbacks.
  - Critical rules: DOB required in v2.1; shared-email mismatch creates new record + alert.
- F-23 Shared Email Alerts and Merge Tool
  - Goal: admin-driven irreversible merge with audit logging.

### Epic D8: Timezone and Scheduling Integrity
- F-24 UTC Storage + Clinic Timezone Conversion
  - Goal: UTC persistence with clinic-local display.
  - Critical rules: timezone required and constrained.
- F-25 DST Transition Handling
  - Goal: timezone-aware slot generation (Luxon), skip invalid times.
  - Critical rules: raw Date arithmetic prohibited.

### Epic D9: Admin Dashboard and Management UI
- F-26 Multi-Provider Dashboard
  - Goal: unified admin view with provider/status/type/date filters.
  - Critical rules: exact status enum consistency.
- F-27 Dashboard Alert Banners
  - Goal: server-computed priority banners for insurance/payment/shared-email.

## 2) Recommended Build Order (Pragmatic)
1. Foundation: F-24, F-25, F-01, F-02, F-03, F-04
2. Booking core: F-05, F-06, F-07
3. Patient integrity and consent: F-22, F-08
4. Comms privacy stack: F-10, F-11, then F-09
5. Payments: F-12 -> F-13 -> F-14 -> F-15 -> F-16
6. Insurance workflow: F-19 -> F-20 -> F-21
7. Waitlist: F-17 -> F-18
8. Admin experience: F-26, F-27, then F-23 merge tooling

## 3) Laravel Boost Capability Test Plan (Project-Specific)

### Phase A: Discovery and Safety Baseline
- Use `application-info` to lock package versions.
- Use `list-routes` to baseline current API surface.
- Use `database-schema` to snapshot tables/indexes before changes.

### Phase B: Per-Feature Development Loop
For each feature:
1. Define acceptance tests first (Feature + Unit).
2. Implement migrations/models/services/controllers/UI.
3. Validate with Boost:
   - `database-schema` for schema/index/constraint checks.
   - `database-query` for read-only data sanity checks.
   - `list-routes` to confirm endpoint registration.
   - `last-error` and `read-log-entries` for backend debugging.
   - `browser-logs` for frontend issues.
4. If behavior is unclear, use `search-docs` (version-correct docs).
5. Use `tinker` only for quick runtime checks, not as test replacement.

### Phase C: Release Readiness Checklist
- All acceptance criteria mapped to tests.
- No critical errors in logs.
- Reservation/payment/insurance cron behaviors verified in staging.
- Timezone and DST edge tests passing.
- HIPAA-sensitive communication rules validated.

## 4) Execution Guidelines for Working Together
1. One feature at a time, vertical slice end-to-end.
2. No large speculative refactors while feature scope is open.
3. Every critical rule in this doc must have at least one automated test.
4. For risky logic (payments, PHI, timezone), require explicit test evidence before merge.
5. Keep a traceability table: `Feature ID -> migration -> endpoint -> test file -> status`.

## 5) First Sprint Recommendation (1-2 weeks)
- Sprint goal: deliver a production-safe booking core without payments.
- Include: F-24, F-25, F-01, F-02, F-03, F-04, F-05, F-06, F-07, F-22, F-08.
- Exit criteria:
  - booking flow works with provider constraints and reservation hold
  - duplicate prevention works with DOB-required flow
  - consent enforced and logged
  - timezone/DST correctness proven by tests

## 6) Implementation Snapshot (as of March 9, 2026)

### Completed
- F-01 Provider CRUD and Status Management
  - Admin API routes + admin UI page implemented.
  - Safe lifecycle rules enforced (cannot remove last active provider; history-aware deactivation behavior).
  - Request classes + policy checks + auth middleware coverage in tests.
- F-02 Provider-Specific Schedule Management
  - Schedule API + admin UI editor implemented.
  - Overlap prevention at validation layer and DB level.
  - PostgreSQL exclusion constraint added for active schedule overlap prevention.
- F-03 Provider Blocked Times
  - Add/remove blocked periods via API and admin UI implemented.
- F-05 6-Step Booking Flow (core)
  - Public APIs and Livewire wizard implemented.
  - Step flow covers patient status, type, provider, slot, details/consent, confirmation.
- F-06 Any Available Auto-Assignment
  - Fewest-today + round-robin tie-break implemented in service and covered by tests.
- F-07 10-Min Slot Reservation Hold
  - Reservation token hold implemented.
  - Expiry handling + every-minute release command scheduled in `routes/console.php`.
- F-08 Consent Collection
  - Booking requires consent (`accepted`) and stores consent payload on patient record.
- F-24 UTC Storage + Clinic Timezone Conversion
  - Clinic timezone conversion service implemented and used across reserve/create flows.
- F-25 DST Transition Handling
  - DST gap rejection implemented and tested.

### Partially Completed / Follow-up Needed
- F-04 Treatment-Provider Bi-Directional Mapping
  - API mapping sync exists in appointment type controller.
  - Follow-up: enforce atomic transaction for appointment type + provider sync and add dedicated acceptance tests.
- F-22 5-Step Matching Algorithm
  - Email+DOB exact match and shared-email handling exist.
  - Follow-up: align fully to documented 5-step algorithm and add explicit alert/audit behavior coverage.

### Not Started Yet
- D3 communications privacy stack (F-09, F-10, F-11)
- D4 payments/deposits (F-12..F-16)
- D5 waitlist priority/notifications (F-17, F-18)
- D6 insurance workflow (F-19..F-21)
- D9 dashboard alerts/filtering depth (F-26, F-27), plus merge tooling (F-23)

## 7) Plan Ahead (Recommended)

### Sprint 2 (Stabilize and close Sprint-1 gaps)
1. Complete F-04 atomic mapping
   - Wrap appointment type update + provider sync in DB transaction.
   - Add feature tests for create/update/delete mapping consistency.
2. Finalize F-22 against SRS
   - Implement full 5-step matching behavior exactly as documented.
   - Add shared-email alert/audit records and tests.
3. Expand booking resilience tests
   - Add tests for reservation release command (`reservations:release-expired`) and idempotency.
   - Add edge tests for provider/type mismatch and consent payload persistence details.
4. Admin appointment type UI
   - Add/manage treatment-provider mapping in admin UI (currently API-first).

### Sprint 3 (Next highest business value)
1. D3 communications privacy stack first: F-10 -> F-11 -> F-09.
2. Then start payments foundation: F-12 and F-13.

### Working Style (continue current workflow)
1. Keep vertical slices: API + service + tests + UI per feature.
2. Add traceability row for each delivered feature: `Feature ID -> endpoints -> classes -> tests -> status`.
3. Treat all critical rules as test-required before feature is marked done.

## 8) Traceability (Live)

| Feature ID | Migration | Endpoint(s) | Tests | Status |
| --- | --- | --- | --- | --- |
| F-10 De-Identified Email | `database/migrations/2026_03_10_090000_create_appointment_access_tokens_table.php` | `/api/public/clinics/{slug}/appointments` | `tests/Feature/DeidentifiedAppointmentEmailTest.php` | Done |
| F-11 Secure Appointment Detail Page | `database/migrations/2026_03_10_090000_create_appointment_access_tokens_table.php` | `/appointments/secure/{token}` | `tests/Feature/SecureAppointmentDetailsTest.php` | Done |
| F-09 Standard PHI Email | N/A | `/api/public/clinics/{slug}/appointments` | `tests/Feature/PhiAppointmentEmailTest.php` | Done |
| F-12 Hybrid Deposit Strategy | `database/migrations/2026_03_13_090000_add_payment_fields_to_appointment_types.php`, `database/migrations/2026_03_13_090100_create_appointment_payments_table.php` | `/api/public/clinics/{slug}/appointments`, `/api/stripe/webhook` | `tests/Feature/PaymentFoundationTest.php`, `tests/Feature/StripeWebhookTest.php` | Done |
| F-13 T-7 Scheduled Auth Job | `database/migrations/2026_03_13_090100_create_appointment_payments_table.php` | `payments:authorize-holds` | `tests/Feature/PaymentFoundationTest.php` | Done |
