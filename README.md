# Invoice & Payment Tracker

A billing management system for tracking client invoices, partial and full payments, and automatically flagging overdue accounts. Built with Laravel and Blade, with a live-updating dashboard powered by Livewire and on-the-fly PDF invoice generation.

## Features

### Client management
- Add, edit, and list clients with name, email, phone, company, and billing address.
- Each client has a dedicated page showing every invoice linked to them, so you can see a client's full billing history at a glance.

### Invoicing with line items
- Create invoices tied to a client with issue date, due date, tax rate, and free-form notes/terms.
- Invoice numbers are auto-generated sequentially (`INV-0001`, `INV-0002`, ...) using a row lock at creation time, so two invoices created at the same instant can never collide on the same number.
- **Line items and the assigned client are protected once an invoice has any recorded payment.** Once money has changed hands, you can't silently change what was billed or who it was billed to — the edit form switches the line items to read-only and disables the client selector, explaining why. Notes, dates, and tax rate remain freely editable regardless of payment status, since none of them affect what was actually paid for.

### Payment tracking
- Record partial or full payments against any invoice, each with its own amount and date.
- Multiple partial payments can stack on a single invoice.
- Status (`Unpaid` → `Partially Paid` → `Paid`) updates automatically and atomically every time a payment is recorded — there's no manual status field for the admin to get wrong.
- A payment can never push an invoice's amount paid past its grand total — the backend rejects overpayment attempts with the exact amount still due, computed with arbitrary-precision decimal math rather than floats.
- Full payment history is visible per invoice.

### Live dashboard (Livewire)
- A single-page financial summary: total invoiced, total collected, outstanding balance, and invoice counts by status — all computed live from the database, not cached or stale.
- Built as a Livewire component with `wire:poll`, so the numbers and the recent-invoices table refresh automatically every 10 seconds without a manual page reload. If you record a payment in one tab, the dashboard in another tab catches up within seconds on its own.
- Livewire automatically throttles polling when the tab isn't in focus, so this doesn't hammer the database for idle, backgrounded sessions.

### PDF invoice generation
- One-click "Download PDF" on every invoice, rendered on the fly with `barryvdh/laravel-dompdf` — no pre-generated files sitting on disk.
- The PDF layout (company header, client details, itemized line items, totals, payment terms) is built with table/float-based CSS
- Designed to actually be sent to a client.

### Dashboard-aside details
- Status badges are colour-coded consistently across the dashboard, invoice list, and invoice detail pages (grey/orange/green/red for unpaid/partially paid/paid/overdue).
- The invoice list is filterable by status and sortable by status or due date, with the sort/filter state preserved across pagination.
- Currency display is driven by a config value (`config('app.currency')`, backed by `APP_CURRENCY` in `.env`) rather than hardcoded — change it once, it updates everywhere it's shown.

## Tech stack

| Layer | Choice |
|---|---|
| Framework | Laravel 13 |
| Frontend | Blade templates, Bootstrap 5.3.3 (CDN), vanilla JS for line-item interactivity |
| Reactive dashboard | Livewire 4 (single-file components) |
| Database | MySQL |
| PDF generation | `barryvdh/laravel-dompdf` |
| Scheduling | Laravel's task scheduler (`routes/console.php`) |
| Auth | Laravel Breeze (Blade flavor) |

### A note on the Laravel version

This was scaffolded with `composer create-project laravel/laravel`, which pulled the latest stable release at the time — Laravel 13. Nothing in the codebase depends on version-specific behavior: the scheduler convention, Eloquent's `casts()` method pattern, and attribute accessors are all stable across recent Laravel versions.

## Architecture notes

A few decisions worth knowing about if you're reading the code, not just running it:

- **Computed financial fields are never stored.** Subtotal, tax amount, grand total, amount paid, amount due, and line totals are all Eloquent accessors computed on the fly from `invoice_items` and `invoice_payments` — never persisted columns. Only `status` is a stored field, because it's a state machine, not a derived value. This avoids the entire category of bugs where a stored total drifts out of sync with the line items or payments that should determine it.
- **Status is intentionally stored, not computed live.** Unlike the financial totals above, `status` represents a state machine driven by events — a payment being recorded, or the daily overdue check running — rather than something derivable purely from current data at read time. That's a deliberate trade-off: it keeps status changes auditable and attributable to a specific event, instead of silently flipping the moment a date happens to pass.
- **All currency columns are `decimal(10,2)`, never `float`/`double`.** Payment-amount comparisons (like the overpayment guard) use PHP's `bccomp`/`bcsub` for exact decimal arithmetic, since floats can't represent values like 0.10 exactly and that's exactly the kind of bug that's invisible until it isn't.
- **Services own business logic, controllers stay thin.** `InvoiceService` and `PaymentService` handle invoice creation/updates and payment recording (including the status-recalculation and overpayment-guard logic) inside database transactions. Controllers validate via Form Requests and delegate.
- **Dashboard aggregation runs in PHP, in memory**, not via SQL aggregate queries. Fine at the data volumes this is built for; would need to move to query-level aggregation (`SUM()`, `GROUP BY` at the DB layer) before it'd hold up at real production scale with a large invoices table.

## Setup

### Requirements

- PHP ^8.3
- Composer
- MySQL 8.0+
- Node.js (for asset compilation — Bootstrap is loaded via CDN, so this is a light build step, not a SPA toolchain)

### Installation

```bash
git clone <repo-url>
cd invoice-tracker

composer install
npm install
npm run build

cp .env.example .env
php artisan key:generate
```

### Database

Set up a MySQL database and point `.env` at it:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=invoice_tracker
DB_USERNAME=root
DB_PASSWORD=your_password
```

Then migrate and seed:

```bash
php artisan migrate --seed
```

This creates all six tables (`users`, `clients`, `invoices`, `invoice_items`, `invoice_payments`, `overdue_logs`) and seeds an admin account plus three sample clients, each with three invoices covering every meaningful status path.

### Default admin login

```
Email:    admin@webtreeonline.com
Password: password
```

### Currency

Default currency display is set via `.env`:

```
APP_CURRENCY=BHD
```

Change to whatever three-letter (or symbol) value you'd like; it's read from `config('app.currency')` everywhere it's displayed, so there's a single place to update.

### Running locally

```bash
php artisan serve
```

Visit `http://127.0.0.1:8000` and log in with the credentials above.

### Scheduler (overdue detection)

The overdue-flagging command is registered in `routes/console.php`:

```php
Schedule::command('invoices:flag-overdue')->daily();
```

```
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

To run it manually, any time:

## Project structure

```
app/
  Services/
    InvoiceService.php      — invoice creation/update, sequential numbering, item/client-edit
                               lockout after payment
    PaymentService.php      — payment recording, overpayment guard, status recalculation
resources/views/
  components/
    ⚡dashboard-stats.blade.php  — Livewire single-file component powering the live dashboard
  pdf/
    invoice.blade.php       — table/float-based layout for DomPDF rendering
database/
  seeders/
    ClientInvoiceSeeder.php — generates realistic sample data via the real service layer,
                               not raw inserts, so seeded data exercises the same code path
                               real usage does
```
