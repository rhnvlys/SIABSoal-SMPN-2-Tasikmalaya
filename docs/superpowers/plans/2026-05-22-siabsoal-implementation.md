# SIABSoal SMPN 2 Tasikmalaya Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the Laravel and Blade SIABSoal application for the requested school workflow from login through T1 to T5 reporting and exports.

**Architecture:** Use Laravel 10 with Eloquent models, Form Requests, route middleware, small controllers, and workflow services for scoring, grouping, analysis, import, reports, exports, and activity logging. Keep views Blade-first with reusable shell components, Bootstrap 5 utilities, custom theme tokens, and server-rendered pages that work in Laragon.

**Tech Stack:** PHP 8.1, Laravel 10, Blade, MySQL/MariaDB, Vite, Bootstrap 5, Laravel Excel, DomPDF, PHPUnit.

---

## File Map

- `database/migrations`, `database/seeders`, `app/Models`: schema, seed data, and model relations.
- `app/Services`: scoring, grouping, item analysis, import, export, reports, and activity logs.
- `app/Http/Controllers`, `app/Http/Requests`, `app/Http/Middleware`: web request surface and authorization guardrails.
- `resources/views`, `resources/css`, `resources/js`: landing, auth, dashboard shell, master data pages, workflow pages, reports, PDF templates, and theme scripts.
- `app/Exports`, `app/Imports`: Laravel Excel adapters and template/report exports.
- `tests/Feature`, `tests/Unit`: TDD coverage for auth, workflow services, data writes, reporting, and exports.

### Task 1: Scaffold And Baseline

**Files:**
- Create: Laravel application skeleton
- Modify: `composer.json`, `package.json`, `.env.example`
- Test: `tests/Feature/LandingPageTest.php`

- [ ] Create a failing landing page feature test that expects the supplied SIABSoal title and login link.
- [ ] Run the landing page test and confirm the failure comes from the missing application route.
- [ ] Scaffold Laravel 10, install Excel, DomPDF, Bootstrap, and baseline frontend tooling.
- [ ] Add the landing route and minimal Blade view that makes the test pass.
- [ ] Run the focused test and the default Laravel test suite.

### Task 2: Schema, Models, Seeders

**Files:**
- Create: migrations for all prompt tables
- Create: models for all prompt entities
- Create: seeders for roles, users, school profile, dummy academic data
- Test: `tests/Feature/DatabaseSeedTest.php`

- [ ] Write a failing database seed test for seeded roles, default users, school profile, and sample exam questions.
- [ ] Run the test to verify schema and seeders are missing.
- [ ] Implement schema constraints, model relations, fillable properties, casts, exam question creation hook, and seeders.
- [ ] Run migrations against the test database and make the seed test pass.

### Task 3: Auth And Role Guardrails

**Files:**
- Create: `AuthController`, `RoleMiddleware`, login views, auth requests/routes
- Modify: `bootstrap/app.php`, `routes/web.php`
- Test: `tests/Feature/AuthAccessTest.php`

- [ ] Write failing tests for seeded role login, inactive login rejection, guest dashboard rejection, and role route restriction.
- [ ] Run tests to verify the protected web surface is absent.
- [ ] Implement username login, logout, middleware aliasing, role helpers, and protected route groups.
- [ ] Run auth tests until green.

### Task 4: Shell, Dashboard, Master Data CRUD

**Files:**
- Create: dashboard controller and Blade shell/components
- Create: CRUD controllers/requests/views for users, guru, siswa, kelas, mapel, tahun ajaran
- Test: feature CRUD tests for siswa, guru, kelas, mapel

- [ ] Write CRUD feature tests for create, update, and delete paths plus dashboard data visibility.
- [ ] Run tests to verify missing screens and routes.
- [ ] Implement reusable Blade layout, sidebar, navbar, alert/form/table/badge components, theme CSS, font-size JS, dashboard cards, and table pages.
- [ ] Implement master CRUD validation and role protection with pagination.
- [ ] Run CRUD and dashboard tests.

### Task 5: Exam And Answer Key Modules

**Files:**
- Create: exam and answer key controllers/requests/views
- Modify: `Ujian` model and exam routes
- Test: `tests/Feature/UjianWorkflowTest.php`

- [ ] Write failing tests for exam creation validation, generated `soal` rows, selected classes, and complete/incomplete keys.
- [ ] Run tests to observe missing workflow behavior.
- [ ] Implement exam create/edit/detail/delete pages, generated questions, class links, key entry grid, and activity logging.
- [ ] Run exam workflow tests.

### Task 6: T1 Scoring And Import Preview

**Files:**
- Create: `ScoringService`, `ImportService`, Data Mentah controller/requests/views
- Create: Excel import/template adapters
- Test: unit scoring tests and feature import preview/confirm tests

- [ ] Write failing tests for answer scoring, binary score scoring, blank/wrong answers, missing keys, duplicate NIS, unmatched students, preview errors, and confirmed saves.
- [ ] Run tests to verify T1 services are missing.
- [ ] Implement answer and binary score processing inside transactions, manual input grid, import preview session payload, confirmation save, and template downloads.
- [ ] Run T1 tests.

### Task 7: T2 And T3 Services

**Files:**
- Create: `GroupingService`, `ItemAnalysisService`
- Create: Olah Data and Analisis Data controllers/views
- Test: `tests/Unit/GroupingServiceTest.php`, `tests/Unit/ItemAnalysisServiceTest.php`, workflow feature tests

- [ ] Write failing tests for stable ranking, 50 percent grouping, odd middle participant, absent exclusion, manual group validation, DP/TK formulas, categories, decisions, and balanced group guardrail.
- [ ] Run tests to verify the analysis services are absent.
- [ ] Implement T2/T3 transaction workflows, badges, warnings, summaries, and exam status transitions.
- [ ] Run unit and workflow feature tests.

### Task 8: Reports And Exports

**Files:**
- Create: `ReportService`, `ExportService`, export adapters, PDF Blade templates
- Create: daftar nilai and rekap nilai controllers/views
- Test: `tests/Feature/ReportExportTest.php`

- [ ] Write failing tests for school-style report summaries and export responses.
- [ ] Run tests to verify report routes are missing.
- [ ] Implement T4 and T5 report pages, analysis summary, Excel downloads, PDF downloads, print templates, and export logs.
- [ ] Run report tests.

### Task 9: README And End-To-End Verification

**Files:**
- Create: `README.md`
- Modify: workflow views and tests where verification exposes gaps
- Test: full suite and rendered browser checks

- [ ] Add README with Laragon installation steps, seeded credentials, workflow, DP/TK formulas, and database name.
- [ ] Run migrations with seed data, full PHPUnit suite, asset build, and route listing.
- [ ] Start the Laravel server, verify landing/login/dashboard/T1/T3 report surfaces in the Browser path at desktop and mobile widths, and check console errors.
- [ ] Record any residual gaps in the final response.
