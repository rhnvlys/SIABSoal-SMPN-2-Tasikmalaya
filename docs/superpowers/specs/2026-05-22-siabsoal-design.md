# SIABSoal SMPN 2 Tasikmalaya Design

## Goal

Build a Laravel and Blade school application for SMPN 2 Tasikmalaya that turns student answer data into the T1 to T5 workflow:

1. T1 Data Mentah and binary scoring.
2. T2 Ranking and upper/lower grouping.
3. T3 item analysis for Daya Pembeda and Tingkat Kesukaran only.
4. T4 Daftar Nilai.
5. T5 Rekap Nilai.

The system remains school-focused. It excludes validity testing, correlations, regression, `r` tables, difference tests, and other advanced statistics not requested by the school.

## Runtime And Stack

- Laravel 10 and Blade because the detected Laragon CLI PHP is PHP 8.1.
- MySQL or MariaDB database named `siabsoal_smpn2`.
- Bootstrap 5 through Vite assets plus focused custom CSS and light JavaScript.
- `maatwebsite/excel` for Excel import/export.
- `barryvdh/laravel-dompdf` for printable PDF exports.
- Local-first deployment that works through Laragon or `php artisan serve`.

## Users And Access

Roles are seeded as Admin, Guru, Operator, and Wakil Kurikulum.

- Admin manages master data, users, school profile, exams, reports, and exports.
- Guru manages owned exams, keys, T1/T2/T3 processing, value reports, and exports.
- Operator manages school master data that supports input and may assist with T1 input/import.
- Wakil Kurikulum reads reports and exports without main data mutations.

All dashboard routes require login. Role middleware constrains module routes. Guru exam access is ownership-based except for Admin and Wakil Kurikulum report access.

## Data Model

The database follows the prompt tables and relations:

- Security and profile: `roles`, `users`, `pengaturan_sekolah`, `log_aktivitas`.
- Master data: `guru`, `siswa`, `tahun_ajaran`, `kelas`, `siswa_kelas`, `mapel`.
- Exam workflow: `ujian`, `ujian_kelas`, `soal`, `peserta_ujian`, `jawaban_siswa`, `analisis_butir`.

Foreign keys, unique constraints, enum-like columns, timestamps, and transaction boundaries protect data consistency. Creating an exam automatically creates numbered `soal` rows and attaches selected classes.

## Workflow Design

### T1 Data Mentah

Teachers or operators can input answers manually, input binary scores manually, or preview Excel/CSV imports before saving. Import cleaning trims cells, uppercases answers, rejects duplicate NIS values, rejects missing matched students by default, validates column counts against exam question count, and stores nothing until confirmation.

For answer mode, all answer keys must be complete. `ScoringService` compares A/B/C/D/E answers with keys, writes binary scores, counts right and wrong answers, computes percentage values, updates attainment, and advances exam status to `data_mentah`.

### T2 Olah Data

`GroupingService` processes only present participants, sorts by value descending and student name ascending, assigns sequential ranking, then assigns upper and lower groups by the default 50 percent method or the configured manual count. Odd present counts leave one middle participant. Absent participants remain visible outside the analysis groups.

### T3 Analisis Data

`ItemAnalysisService` requires balanced upper and lower groups. For each question it calculates:

- `DP = (BA - BB) / JA`
- `TK = (BA + BB) / N`

The service stores categories and decisions exactly from the requested thresholds. Formula code keeps short explanatory comments suitable for KP reporting.

### T4 And T5 Reports

`ReportService` gathers the school-style value list and recap summary with eager-loaded exam relations. `ExportService` provides Excel and PDF exports for required report surfaces and records export activity.

## UI Design

The first public screen is a focused landing page with the supplied SIABSoal name, full system name, description, feature list, and login action. Authenticated screens use a restrained dashboard shell:

- Sidebar with the final 16 menu labels from the prompt.
- Navbar with dark/light mode toggle and normal/large font controls stored in `localStorage`.
- Accessible tables, clear badges, explicit loading states, alerts, confirmation modal hooks, and horizontal scroll for wide T1 tables.
- Light and dark color tokens from the prompt with readable sizing and minimal visual noise.

The dashboard prioritizes counts, latest exams, and shortcut actions. Master data uses conventional table and form pages. Workflow pages put exam selection, one short instruction, processing actions, and results in that order.

## Error Handling And Audit

Form Requests validate writes. Batch writes use transactions. Import preview returns row-level errors. Human messages replace raw technical errors. Important actions are stored in `log_aktivitas`: imports, T1/T2/T3 processing, exam edits/deletes, and exports.

## Testing

Feature tests cover authentication, role protection, core CRUD paths, exam and key behavior, T1 scoring/import validation, T2 grouping, T3 DP/TK calculation, report summaries, and export responses. Unit tests focus on service formulas and decisions. Render checks validate landing/dashboard assets, theme toggles, wide tables, and responsive shell behavior.

## Scope Notes

This initial implementation will favor complete end-to-end school workflow coverage over deep polish on every secondary CRUD screen. It will include the requested tables, services, views, exports, seed data, README, and tests while keeping the project understandable for a KP report.
