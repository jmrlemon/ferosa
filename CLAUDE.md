# Ferosa Landscaping

Landscaping business system for Orani, Bataan: a plant/materials shop with a
persistent server-side cart, service appointment booking, customer↔staff chat,
an admin workspace, and a native Android companion app with AR plant placement.

## Layout

| Path | What it is |
|---|---|
| `ferosa-laravel/` | The application. Laravel 12, PHP 8.2+, MySQL. **All PHP work happens here.** |
| `ferosa_mobile/` | Native Android app (Kotlin, Compose, ARCore/SceneView). Hybrid: a persistent WebView renders the Laravel UI, native screens handle AR and the estimator. |
| `.kiro/`, `.idea/` | Other tools' workspaces. Untracked now, still on disk. |

## Running things

Everything below runs from `ferosa-laravel/`.

```
composer dev        # server + queue listener + vite, all at once (Windows-only paths)
composer check      # lint + static analysis + tests - run this before you commit
composer lint:fix   # apply Pint formatting
php artisan test
```

- **The queue worker must be running** or customers get no order/appointment
  notifications and no mail: `start-worker.bat`, or `php artisan queue:work`.
  Mailables and notifications are all `ShouldQueue`.
- The scheduler (`php artisan schedule:work`) drives appointment reminders and
  the nightly `db:backup`.

## Conventions that are load-bearing

**Tests run on in-memory SQLite.** `tests/TestCase.php` aborts the run if a
cached config exists or if the connection is not sqlite — a stale
`bootstrap/cache/config.php` once made `RefreshDatabase` drop every table in the
live MySQL database. Do not "fix" that guard. Run `php artisan config:clear`
if tests refuse to start.

**Blade JavaScript is linted separately.** `npm run lint:blade-js`
(`scripts/check-blade-js.mjs`) extracts the JS out of every `.blade.php` and
parses it. This exists because a corrupted byte in `admin/dashboard.blade.php`
split a string literal and silently killed every handler on the page while
`php -l` and PHPUnit both passed. It runs in `npm run build` and in CI.

**Uploads: public disk vs private disk is a security decision, not a
preference.** Anything a customer would consider private — payment proofs, chat
attachments — goes on the `local` disk and is served through a route that
checks ownership (`PageController::paymentProof`,
`MessageController::attachment`). Only genuinely public product assets (images,
AR models) go on the `public` disk. Follow the existing pattern rather than
inventing a third one.

**Authorization is middleware + explicit checks.** There are no Policies. Role
gates are the `staff` and `admin` route middleware (`EnsureStaff`,
`EnsureAdmin`); per-record ownership is an explicit
`abort_unless((int) $x->user_id === (int) auth()->id(), 403)` at the top of the
action. If you add an action that loads a record by ID, add that line.

**Orders and appointments are state machines.** `Order::STATUS_TRANSITIONS` and
`Appointment::STATUS_TRANSITIONS` with `canTransitionTo()`. Do not set `status`
directly; go through the transition check. Stock and totals are always
recalculated server-side — never trust a posted price or quantity.

**Static analysis has a baseline.** `phpstan-baseline.neon` records 39
pre-existing findings so CI blocks *new* ones. Shrink it; don't add to it.

## Android ↔ Laravel

The app does not use tokens. `WebViewCookieJar` + `AuthInterceptor` forward the
WebView's Laravel **session and XSRF cookies** to the Retrofit client, which is
why `routes/api.php` is `middleware(['web', 'auth'])` and why those POST routes
still need a CSRF token. Base URL is `BuildConfig.SERVER_URL`
(`-PFEROSA_SERVER_URL=...`), defaulting to the emulator host `10.0.2.2`.

The web layout detects the app by its `FerosaApp/1.0` User-Agent and renders
`class="... in-app"` on `<body>` on the first response, so the web nav never
flashes under the native one — `tests/Feature/MobileAppLayoutTest.php` guards
this. The four AR endpoints in `routes/api.php` are mirrored in
`data/api/ApiService.kt`; change one and change the other.

## Deployment gotcha

The project is served from inside `htdocs`, so Apache's DocumentRoot is *above*
`ferosa-laravel/public`. `ferosa-laravel/.htaccess` denies the whole application
tree and `public/.htaccess` re-grants itself; without that pair, `.env`,
`storage/` and `vendor/` are fetchable over HTTP. Pointing the vhost at
`ferosa-laravel/public` is the real fix. After any web-server change, verify:

```
curl -o /dev/null -w '%{http_code}' http://<host>/ferosa/ferosa-laravel/.env   # expect 403
```
