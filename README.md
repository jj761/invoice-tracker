# Invoice & Payment Tracker
 
A billing management tool built for Webtree Software Solutions W.L.L's Internal Hackathon (Intern 3 task). Admins can create clients, build invoices with line items, record partial or full payments, automatically flag overdue invoices via a daily scheduled command, generate professional PDF invoices, and view a financial summary dashboard.
 
## Stack
 
- Laravel 13.17.0 (see note below)
- MySQL 8.0
- Blade templating (no Vue/React/Inertia)
- Bootstrap 5.3.3 (CDN, no build-step frontend tooling)
- `barryvdh/laravel-dompdf` for PDF generation
- Laravel Task Scheduling for overdue detection
- Laravel Breeze (Blade flavor) for authentication
### A note on the Laravel version
 
The original task brief specifies Laravel 11 as the fixed stack. This project was scaffolded with `composer create-project laravel/laravel`, which pulled the latest stable release at the time — Laravel 13.17.0 — rather than a pinned Laravel 11 version. This was not a deliberate substitution; it reflects what `composer.json` actually resolved to and is documented here rather than left as a silent discrepancy.
 
Nothing in this implementation depends on Laravel 11-specific behavior that changed in later versions. Specifically:
 
- The scheduler convention used (`routes/console.php` instead of `app/Console/Kernel.php`) was introduced in Laravel 11 and remains the correct, supported convention in Laravel 13 — it is not an L11-only workaround.
- Eloquent's `casts()` method pattern (used across all models) and attribute accessors (`Attribute::make()`) are stable across this version range.
- No Laravel 11-specific package or syntax was used anywhere in this codebase that would behave differently under Laravel 13.
Functionally, the deliverable behaves identically to what an L11-pinned install would produce. If a strict Laravel 11 install is required for evaluation, re-running `composer require laravel/framework:^11.0` against a clean clone is expected to work without code changes, though this has not been tested against that exact version.
 
## Setup
 
### Requirements
 
- PHP ^8.3
- Composer
- MySQL 8.0+
- Node.js (for `npm run build`, asset compilation only — no SPA framework is used)
### Installation
 
```bash
git clone https://github.com/jithinpriv/invoice-tracker.git
cd invoice-tracker
 
composer install
npm install
npm run build
 
cp .env.example .env
php artisan key:generate
```
 
### Database
 
Create a MySQL database (default expected name: `invoice_tracker`), then set the following in `.env`:
 
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=invoice_tracker
DB_USERNAME=root
DB_PASSWORD=your_password
```
 
Run migrations and seed sample data:
 
```bash
php artisan migrate --seed
```
 
This creates all 6 tables (`users`, `clients`, `invoices`, `invoice_items`, `invoice_payments`, `overdue_logs`), seeds one admin user, and seeds 3 sample clients each with 3 invoices covering every meaningful status path (fully paid past-due, partially paid future-due, unpaid past-due).
 
### Seeded admin credentials
 
```
Email:    admin@webtreeonline.com
Password: password
```
 
Change this password before deploying anywhere beyond local evaluation — it is seeded in plain text for hackathon convenience only.
 
### DomPDF installation
 
`barryvdh/laravel-dompdf` is already listed in `composer.json` and installs automatically with `composer install` — no separate install step is required. If the package's config needs publishing for any reason:
 
```bash
php artisan vendor:publish --provider="Barryvdh\DomPDF\ServiceProvider"
```
 
This is not required for the app to function as shipped; DomPDF works with sensible defaults out of the box here.
 
### Running the app
 
```bash
php artisan serve
```
 
Visit `http://127.0.0.1:8000`, log in with the seeded admin credentials above.
 
### Scheduler setup (overdue invoice detection)
 
The daily overdue-flagging command is registered in `routes/console.php`:
 
```php
Schedule::command('invoices:flag-overdue')->daily();
```
 
**Why `routes/console.php` and not `Kernel.php`:** the original brief references `Kernel.php` for scheduler registration, which was the convention in Laravel 10 and earlier. Laravel 11 removed the default `app/Console/Kernel.php` scaffold and moved scheduled command registration to `routes/console.php` instead — this is the current, correct convention for any Laravel 11+ project (including this one, on 13.17.0), not a deviation from best practice.
 
For production, add this cron entry (runs every minute; Laravel's scheduler internally decides what's actually due to run):
 
```
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```
 
To test the command manually without waiting on cron/the scheduler's timing window:
 
```bash
php artisan invoices:flag-overdue
```
 
This command is idempotent — it only transitions `unpaid` or `partially_paid` invoices with a past due date to `overdue`, and never touches invoices already marked `overdue` or `paid`. Running it repeatedly with no new overdue invoices reports zero flagged.
 
## Architecture notes / known assumptions
 
- **Authentication:** single admin role only, per the brief — no multi-user or permission system implemented.
- **Computed financial fields** (`subtotal`, `tax_amount`, `grand_total`, `amount_paid`, `amount_due`, `line_total`) are never stored in the database — they're calculated on the fly via Eloquent accessors from `invoice_items` and `invoice_payments`. Only `status` is a persisted, stateful column, since it represents a state machine rather than a derived value.
- **Client deletion is intentionally not implemented.** The brief asks for add/edit/list only; no `destroy` route or controller method exists for clients. This was a scope decision, not an oversight.
- **Dashboard aggregation is done in-memory** (loading invoices and summing in PHP) rather than via database-level aggregate queries. This is acceptable at the data volumes involved in this hackathon deliverable but would not be the right approach at production scale with a large invoices table.
- **The optional monthly invoiced-vs-collected chart was skipped.** The brief marks this as explicitly optional; it was deprioritized to stay within the same-day deadline in favor of fully completing every non-optional requirement first.
- **Open ambiguity — overdue status and partial payments:** the brief doesn't specify what should happen if a payment is recorded against an invoice that's already been flagged `overdue`. The current implementation's behavior: `PaymentService::recalculateStatus()` only ever sets `paid`, `partially_paid`, or `unpaid` — it never sets or preserves `overdue`. So recording any payment against an overdue invoice immediately moves it to `partially_paid` (or `paid`), and the overdue badge disappears even though the due date is still in the past. The historical fact that it was once flagged overdue is preserved separately in the `overdue_logs` table (an append-only audit log), so that information isn't lost — it just isn't reflected in the live `status` badge anymore once a payment lands. This was a judgment call in the absence of an explicit spec; an alternative design could special-case "don't let payment recalculation override an `overdue` status unless the invoice is now fully paid," but that wasn't implemented here.
- **Sequential invoice numbering** (`INV-0001`, `INV-0002`, ...) is generated inside `InvoiceService::create()` using `lockForUpdate()` on the most recent invoice row, to avoid race conditions if two invoices were created concurrently.
- **Decimal handling:** all currency columns are `decimal(10,2)`, never `float`/`double`, and payment-amount comparisons (e.g. the overpayment guard in `PaymentService`) use PHP's `bccomp`/`bcsub` for exact decimal arithmetic rather than native float comparison.
## Dual-repository transparency note
 
In addition to the work repository above, this project is also mirrored to a personal GitHub portfolio repository under a separate account, with mentor approval. Commits in the personal mirror are re-authored to a personal git identity but contain identical code and history content to this repository at each synced point. This is disclosed here for transparency; the work repository (`jithinpriv/invoice-tracker`) is the authoritative source.
 
## Requirement-to-evaluation-criteria checklist
 
| Brief criterion | Status | Notes |
|---|---|---|
| Single admin login, all routes behind `auth` middleware | Done | Breeze (Blade), `Route::middleware('auth')` wraps all app routes |
| Admin seeded in database seeder | Done | `admin@webtreeonline.com` / `password` via `DatabaseSeeder` |
| Client add/edit/list | Done | No delete, per brief scope |
| Client fields: Name, Email, Phone, Company Name, Billing Address | Done | |
| Client page shows linked invoices | Done | `clients/show.blade.php` |
| Invoice: auto-generated number, Issue Date, Due Date, Notes | Done | Sequential `INV-0001` format, race-safe via `lockForUpdate()` |
| Multiple line items: Description, Quantity, Unit Price, auto Line Total | Done | Computed accessor, never stored |
| Configurable Tax % at invoice level | Done | |
| Auto-calculated Subtotal, Tax Amount, Grand Total | Done | All via Eloquent accessors |
| Payment status: Unpaid / Partially Paid / Paid | Done | Plus Overdue as a 4th state |
| Record payment: amount + date | Done | |
| Multiple partial payments per invoice | Done | |
| Auto status update on payment | Done | `PaymentService::recalculateStatus()` |
| Payment history log per invoice | Done | `invoices/show.blade.php` |
| Daily scheduled overdue-check command | Done | `invoices:flag-overdue`, registered in `routes/console.php` |
| Mark Overdue if due date passed and not fully Paid | Done | Never touches already-`paid` or already-`overdue` rows |
| Dashboard/list visually highlight Overdue with distinct badge | Done | Color-coded status badges throughout |
| Log date/time when auto-marked Overdue | Done | `overdue_logs` table, append-only |
| DomPDF installed and configured | Done | |
| PDF Blade view: header, client details, line items, totals, terms | Done | Table/float-based CSS, no flexbox/grid (DomPDF constraint) |
| Download PDF button per invoice | Done | |
| Dashboard: Total Invoiced, Total Collected, Outstanding, status counts | Done | In-memory aggregation |
| Recent invoices list (last 10) with status badges | Done | |
| Optional monthly chart | Skipped | Explicitly optional per brief; deprioritized for same-day deadline |
| Tables: `users`, `clients`, `invoices`, `invoice_items`, `invoice_payments`, `overdue_logs` | Done | All 6, with migrations |
| Seeder: admin + at least 3 sample clients with invoices | Done | 3 clients × 3 invoices each (9 total), covering paid/partial/unpaid-overdue-candidate states |
| Foreign keys + cascade deletes | Done | All FKs use `cascadeOnDelete()` |
| Eloquent accessors for computed fields | Done | `amount_due` and all other derived totals |
| Clean controllers, Form Requests, service methods | Done | `ClientRequest`, `InvoiceRequest`, `PaymentRequest`; `InvoiceService`, `PaymentService` |
| Color-coded status badges | Done | |
| Dashboard numbers stand out | Done | |
| Tables sortable by status and date | Done | `invoices/index.blade.php`, query-string preserving sort links |
| Clean commit history | Done | Regular, descriptive commits throughout |
| README: setup, DomPDF, seeder creds, scheduler setup | Done | This document |
