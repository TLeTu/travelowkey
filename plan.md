# Auth Hardening Plan & Implementation Summary

This document captures the minimal, focused changes implemented to add JWT-based authentication, password hashing, and tighter authorization checks to the project with minimal UI disruption.

## Goals
- Hash passwords using `password_hash`/`password_verify` with seamless migration from legacy plaintext.
- Authenticate via an HttpOnly, SameSite=Strict JWT cookie (`auth_token`) with 1h expiry.
- Derive authenticated user solely server-side; ignore client-sent `userId`.
- Protect sensitive endpoints and enforce bill ownership.
- Keep UI changes minimal; progressively remove reliance on client cookies for auth decisions.

## Backend Changes
- Added `server/data-controller/auth.php`
	- `jwt_config()` with `secret`, `iss`, `aud`, `expSeconds`, and `cookieName='auth_token'`.
	- `sign_jwt()`, `verify_jwt()` (HS256 with base64url utilities).
	- Cookie helpers: `set_auth_cookie()`, `clear_auth_cookie()`, `get_auth_token_from_cookie()`.
	- `get_auth_user_id()` and `require_auth()` to gate endpoints and derive `sub` (user id) from JWT.

- Signup: `pages/signup/signup.php`
	- Stores hashed passwords with `password_hash(PASSWORD_DEFAULT)`.

- Login: `pages/login/login.php`
	- Verifies with `password_verify()`; supports legacy plaintext by auto-migrating to hash on first successful login.
	- Issues JWT cookie via `set_auth_cookie()`; returns `{ success, userId }` for UI continuity.

- User check: `server/data-controller/check-user-info.php`
	- Prefers JWT cookie to identify user; legacy `userId` GET fallback retained temporarily but not relied upon.

- Account endpoints: `server/data-controller/account/*`
	- `get-data.php`, `update-data.php`, `change-password.php` now call `require_auth()` and operate on JWT-derived user id.
	- `change-password.php` verifies old password (hash or legacy) and saves new hashed password.

- Payment creation endpoints: `server/data-controller/payment-*/post-data.php`
	- Gated with `require_auth()`; ignore posted `userId`.

- Bill detail endpoints: `server/data-controller/bill-detail/get-data.php`
	- Gated with `require_auth()`; join invoice tables and enforce ownership (only owner can retrieve details).

- Logout: `server/data-controller/auth-logout.php`
	- Clears JWT cookie and returns `{ ok: true }`.

- SQL migration: `sql/migrations/001_user_password_widen.sql`
	- `ALTER TABLE user MODIFY Password VARCHAR(255)` to support bcrypt length.

## Frontend Changes
- Removed reliance on `userId` cookie across payment and account flows.
- Converted synchronous XHR “login guard” checks to async `fetch` and server-based profile completeness checks:
	- `pages/coach-display/js/coach-search-result.js`
	- `pages/transfer-display/js/transfer-display.js`
	- `pages/hotel-display/js/hotel-search-result.js`
	- `pages/flight-display/js/flight-display.js`
	- Each defines a small `checkLoginAndProfileComplete()` that calls `check-user-info` and computes completeness client-side without cookies.

- Account UI:
	- `pages/account/js/account-auth.js`: On load, fetches user info and shows the edit form if profile incomplete (no `userAuth` cookie needed).
	- `pages/account/js/account.js`: Removed cookie-based completeness logic; `CheckUserInfo()` no-op.
	- `pages/account/js/account-navbar.js`: Still clears legacy `userId` cookie on logout to clean old state.

- Login UI: `pages/login/js/login.js`
	- After login, fetches profile info server-side and redirects to `main` or `account` based on completeness; removed `userAuth` cookie usage.

- Signup UI: `pages/signup/js/signup.js`
	- Removed setting `userId` cookie and auto-login; redirects to login so server can issue JWT.

## Security & Behavior Notes
- Auth is enforced server-side via JWT cookie only; client-provided `userId` is ignored.
- Ownership checks ensure a user cannot access another user’s invoices.
- JWT cookie flags: HttpOnly, SameSite=Strict; `Secure` enabled automatically under HTTPS.
- Set a strong `JWT_SECRET` in production.

## Optional (Deferred) Refinements
- Centralize the `checkLoginAndProfileComplete()` helper into a shared `resources/js/check-logged-in.js` for DRY code.
- Add a tiny `auth/profile-status` endpoint that returns `{ loggedIn, profileComplete }` to avoid fetching full user data for guards.

## Testing
- `SMOKE_TESTS.md` updated with revised flows, UI guard tests, and curl commands for login, auth, protected endpoints, and logout.

## Impact Summary
- Minimal schema change (password column widen).
- Localized backend additions and endpoint guards.
- Frontend guards switched to server checks; removed `userId` and `userAuth` cookie reliance for auth decisions.
- Existing UI navigation preserved, with cleaner and safer auth handling.

---

<!-- VNPAY section removed per request; project restored to pre-VNPAY state -->

