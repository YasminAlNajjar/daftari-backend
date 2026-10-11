# Daftari Backend

Daftari is a Laravel REST API for managing customers, debts, payments, reports, notifications, and offline-created records. It is designed for a Flutter client and supports safe retry-based synchronization through client-generated UUIDs.

## Main Features

- Email OTP authentication and Laravel Sanctum API tokens.
- Customer management with credit limits and soft deletion.
- Debt and payment transaction management.
- Offline creation and idempotent synchronization of customers and transactions.
- Financial summaries, daily inventory, customer statements, transaction reports, and customer balance reports.
- Asynchronous PDF generation with verification certificates.
- In-app and Firebase push notifications.
- Daily data-entry reminders.
- Configurable PDF export limits and separate queues for reports and notifications.
- Optimized customer balance queries without N+1 queries.

## Technology Stack

- PHP 8.2+
- Laravel 12
- Laravel Sanctum 4
- MySQL or SQLite
- Database-backed queues
- mPDF
- Firebase Cloud Messaging
- Brevo transactional email
- S3-compatible storage for production report files

## Requirements

- PHP 8.2 or newer
- Composer
- MySQL 8+ or SQLite
- Node.js and npm
- Required PHP extensions for Laravel, database access, and mPDF

## Local Installation

Clone the repository and enter the project directory:

```bash
git clone https://github.com/YasminAlNajjar/daftari-backend.git
cd daftari-backend
```

Install and initialize the application:

```bash
composer run setup
```

The setup command installs PHP and JavaScript dependencies, creates `.env`, generates the application key, runs migrations, and builds frontend assets.

Alternatively, run the steps manually:

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build
```

On Windows PowerShell, create the environment file with:

```powershell
Copy-Item .env.example .env
```

## Environment Configuration

Start from `.env.example` and never commit real credentials or service-account files.

### Application

```env
APP_NAME=Daftari
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_TIMEZONE=Asia/Hebron
```

Production must use `APP_ENV=production`, `APP_DEBUG=false`, HTTPS, and a secure generated `APP_KEY`.

### Database

SQLite is suitable for local development. For MySQL, use:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=daftari
DB_USERNAME=root
DB_PASSWORD=
```

Then run:

```bash
php artisan migrate --force
```

### OTP Email

OTP messages are sent through Brevo:

```env
BREVO_API_KEY=
BREVO_SENDER_EMAIL=
BREVO_SENDER_NAME=Daftari
```

### Firebase Push Notifications

Firebase is disabled by default. It can use either a local credentials file or a base64-encoded service-account JSON value:

```env
FIREBASE_ENABLED=false
FIREBASE_PROJECT_ID=
FIREBASE_CREDENTIALS=
FIREBASE_CREDENTIALS_BASE64=
```

Use `FIREBASE_CREDENTIALS_BASE64` in hosted environments where mounting a private credentials file is not practical.

### Report Storage

Local development stores generated reports on the private local disk:

```env
REPORTS_DISK=local
```

For private S3-compatible storage:

```env
REPORTS_DISK=reports
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_ENDPOINT=
AWS_USE_PATH_STYLE_ENDPOINT=false
```

## Running the Application

Start the complete development environment:

```bash
composer run dev
```

This starts the Laravel server, Vite, logs, the notification worker, and the report worker.

To run only the API server:

```bash
php artisan serve
```

The default local API URL is:

```text
http://localhost:8000/api
```

## Authentication

Authentication uses email OTP and Laravel Sanctum.

Public authentication endpoints:

```text
POST /api/auth/otp/send
POST /api/auth/otp/verify
POST /api/auth/complete-profile
```

All remaining application endpoints require:

```http
Authorization: Bearer <access_token>
Accept: application/json
```

Authenticated session endpoints:

```text
GET  /api/auth/me
POST /api/auth/logout
```

## API Overview

### Customers

```text
POST   /api/customers
GET    /api/customers
GET    /api/customers/{customer}
PATCH  /api/customers/{customer}
DELETE /api/customers/{customer}
```

### Transactions

```text
POST   /api/customers/{customer}/transactions
GET    /api/customers/{customer}/transactions
GET    /api/customers/{customer}/transactions/{transaction}
PATCH  /api/customers/{customer}/transactions/{transaction}
DELETE /api/customers/{customer}/transactions/{transaction}
```

Supported transaction types are `debt` and `payment`.

### Reports

```text
GET /api/reports/summary
GET /api/reports/daily
GET /api/reports/customer-statement
GET /api/reports/transactions
GET /api/reports/customer-balances
```

Asynchronous PDF exports:

```text
POST /api/reports/exports
GET  /api/reports/exports/{id}
GET  /api/reports/exports/{export}/download
GET  /api/reports/verify/{token}
```

Supported PDF report types:

- `daily_inventory`
- `customer_statement`
- `general_transactions`
- `customer_balances`

### Notifications and Devices

```text
GET    /api/notifications
GET    /api/notifications/unread-count
PATCH  /api/notifications/{id}/read
POST   /api/device-tokens
DELETE /api/device-tokens
```

## Offline Synchronization

Offline support is limited to creating customers and transactions. Authentication, updates, deletion, and reports require an internet connection.

The Flutter application generates a UUID for every offline-created customer and transaction and sends it as `client_uuid` when synchronizing. The backend scopes UUID uniqueness to the authenticated user and safely handles retries:

- A new UUID creates the record.
- Repeating the same UUID with the same data returns the existing record.
- Reusing the UUID with different data returns `409 CLIENT_UUID_CONFLICT`.
- Retrying a transaction never creates the debt or payment twice.

Successful create responses include both identifiers so the client can map its local row to the server row:

```json
{
  "id": 52,
  "client_uuid": "8ef70fe8-7a4b-4ff5-8ea3-9a1953ea8532"
}
```

Customers must be synchronized first. Transactions are sent only after the related customer has a server ID, and transactions must be synchronized in their original local creation order.

## Customer Balance Performance

Customer balances are calculated in the customer-list database query. This keeps the list query count constant as the number of customers grows and prevents N+1 database queries.

The existing response format and calculation remain unchanged:

```text
balance = payments - debts
```

Soft-deleted transactions remain excluded through the model's existing global scope.

## PDF Export Limits

PDF export rows are bounded to protect worker memory:

```env
REPORT_EXPORT_MAX_ROWS=10000
```

When an export exceeds the configured limit, generation stops with:

```text
REPORT_EXPORT_ROW_LIMIT_EXCEEDED
```

The limit applies only to full PDF exports. Existing paginated report API responses are unchanged.

## Queue Configuration

The application uses separate queues so long-running PDFs do not delay push notifications:

```env
QUEUE_CONNECTION=database
DB_QUEUE_RETRY_AFTER=360
REPORTS_QUEUE=reports
NOTIFICATIONS_QUEUE=notifications
```

Run both workers in production under Supervisor, systemd, or another process manager:

```bash
php artisan queue:work --queue=notifications,default --tries=4 --timeout=60
php artisan queue:work --queue=reports --tries=2 --timeout=300
```

PDF jobs use two attempts, a 300-second timeout, a 60-second retry delay, and fail on timeout. `DB_QUEUE_RETRY_AFTER` must remain greater than the report timeout to prevent the same job from being processed concurrently.

After changing queue configuration:

```bash
php artisan optimize:clear
php artisan config:cache
php artisan queue:restart
```

## Scheduler

The scheduler performs two recurring tasks:

- Removes expired report exports every hour.
- Creates daily data-entry reminders at the configured local time.

Notification schedule settings:

```env
NOTIFICATIONS_CREDIT_LIMIT_WARNING_PERCENTAGE=80
NOTIFICATIONS_DAILY_REMINDER_TIME=21:00
NOTIFICATIONS_TIMEZONE=Asia/Hebron
```

Run the scheduler continuously in production:

```bash
php artisan schedule:work
```

Or add the standard Laravel cron entry:

```cron
* * * * * cd /path/to/daftari-backend && php artisan schedule:run >> /dev/null 2>&1
```

## Security Notes

- Protected routes use `auth:sanctum`.
- Customer, transaction, report, notification, and device operations are scoped to the authenticated user.
- Laravel Form Requests validate API input.
- Offline UUID uniqueness is enforced at the database level per user.
- Report generation jobs are encrypted before being stored in the queue.
- Generated report storage is private.
- Report download and customer-specific report generation verify ownership.
- OTP codes are hashed, expire, and have attempt and resend limits.
- Keep `APP_DEBUG=false` and never store credentials in the repository in production.

## Testing

Run the complete test suite:

```bash
composer test
```

Or:

```bash
php artisan test
```

The automated tests cover:

- Notification and daily-reminder behavior.
- Offline customer and transaction idempotency.
- Duplicate UUID and duplicate phone conflicts.
- Credit-limit validation and notifications.
- Device-token ownership.
- Constant customer-list query count.
- Single-query customer balance calculation.
- PDF export row limits.
- Separation of report and notification queues.

Code style can be checked with:

```bash
vendor/bin/pint --test
```

## Useful Commands

```bash
php artisan migrate
php artisan route:list --path=api
php artisan queue:failed
php artisan queue:retry all
php artisan schedule:list
php artisan optimize:clear
```

## Production Checklist

- Set `APP_ENV=production` and `APP_DEBUG=false`.
- Configure HTTPS and the correct `APP_URL`.
- Configure MySQL and run migrations.
- Configure Brevo credentials for OTP delivery.
- Configure Firebase only when push notifications are enabled.
- Use private S3-compatible report storage when local storage is not persistent.
- Run both queue workers with automatic restart policies.
- Run the Laravel scheduler.
- Cache configuration after deployment.
- Protect `.env`, Firebase credentials, and all cloud service keys.

## License

This project is built with Laravel, which is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
